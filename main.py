from flask import Flask, render_template, session, request, redirect, url_for, flash
import sqlite3


app = Flask(__name__)
app.secret_key = 'dupa' # XXX What is it for and how does it work?

con = sqlite3.connect('snooker.db', check_same_thread=False)


@app.route('/index.html')
def show_index():
    username = session['username'] if 'username' in session else None
    return render_template('index.html', username=username)


@app.route('/profile.html')
def show_profile():
    username = None
    if 'username' in session:
        for row in con.execute('SELECT * FROM users WHERE username=?', [session['username']]):
            user_id, username, password, date_of_birth = row
    return render_template('profile.html', user_id=user_id, username=username, password=password,
                           date_of_birth=date_of_birth)


@app.route('/register.html')
def show_register():
    username = session['username'] if 'username' in session else None
    return render_template('register.html', username=username)


@app.route('/generic_stats.html')
def show_generic_stats():
    matches = []
    for row in con.execute("SELECT IFNULL(B.username,'') player1,\
                           IFNULL(C.username,'') player2,\
                           IFNULL(D.clubname,'') clubname,\
                           A.id, A.bestof, A.date, A.p1_score, A.p2_score FROM matches A\
                           LEFT JOIN users B ON A.player1 = B.id\
                           LEFT JOIN users C ON A.player2 = C.id\
                           LEFT JOIN clubs D ON A.club = D.id\
                           WHERE (player1=? OR player2=?) AND finished='true'",
                           [session['id'], session['id']]):
        match = {}
        match['player1'] = row[0]
        match['player2'] = row[1]
        match['clubname'] = row[2]
        match['id'] = row[3]
        match['bestof'] = row[4]
        match['date'] = row[5]
        match['p1_score'] = row[6]
        match['p2_score'] = row[7]
        matches.append(match)
    won = {'matches': 0, 'frames': 0}
    lost = {'matches': 0, 'frames': 0}
    for match in matches:
        if session['username'] == match['player1']:
            won['frames'] += int(match['p1_score'])
            lost['frames'] += int(match['p2_score'])
            if int(match['p1_score']) > int(match['p2_score']):
                won['matches'] += 1
            else:
                lost['matches'] += 1
        else:
            won['frames'] += int(match['p2_score'])
            lost['frames'] += int(match['p1_score'])
            if int(match['p2_score']) > int(match['p1_score']):
                won['matches'] += 1
            else:
                lost['matches'] += 1
    username = session['username'] if 'username' in session else None
    return render_template('generic_stats.html', username=username, matches=matches, won=won, lost=lost)


@app.route('/detailed_stats.html', methods=['POST'])
def show_detailed_stats():
    match_id = request.form['show_match']
    for row in con.execute('SELECT * FROM matches WHERE id=?', [match_id]):
        player1 = 'you' if row[1] == session['id'] else 'opponent'
        match_logs = parse_logs(row[5])
        # Check which player are you.
    scoring = get_scoring(match_logs, player1)
    print(scoring)
    print(scoring['time'])
    print(len(scoring['time']))
    print(len(scoring['you']))
    print(len(scoring['opponent']))
    
    username = session['username'] if 'username' in session else None
    return render_template('detailed_stats.html', username=username, scoring=scoring)


@app.route('/create_match.html')
def show_create_match():
    if 'match_id' in session:
        return redirect(url_for('show_scoreboard'))
    players = [row[1] for row in con.execute('SELECT * FROM users ORDER BY username ASC')]
    clubs = [row[1] for row in con.execute('SELECT * FROM clubs ORDER BY clubname ASC')]
    username = session['username'] if 'username' in session else None
    return render_template('create_match.html', username=username, players=players, clubs=clubs)


@app.route('/scoreboard.html')
def show_scoreboard():
    # Show match which id is in session['match_id]
    match_id = session['match_id']
    match_info = 'Nothing yet.'
    username = session['username'] if 'username' in session else None
    return render_template('scoreboard.html', username=username, match_info=match_info)


@app.route('/login', methods=['POST'])
def log_in():
    # XXX Validate form.
    username = request.form['username']
    password = request.form['password']
    for row in con.execute('SELECT * FROM users WHERE username=?', [username]):
        if row[2] == password:
            session['username'] = username
            session['id'] = row[0]
    return redirect(url_for('show_index'))


@app.route('/logout', methods=['POST'])
def log_out():
    del session['username']
    return redirect(url_for('show_index'))


@app.route('/register', methods=['POST'])
def register():
    # XXX Validate form.
    reg_name = request.form['reg_name']
    reg_surname = request.form['reg_surname']
    reg_password = request.form['reg_password']
    reg_dob = request.form['reg_dob']
    reg_username = reg_name + ' ' + reg_surname
    try:
        with con:
            con.execute('INSERT INTO users(username, password, date_of_birth) VALUES (?, ?, ?)',
                        [reg_username, reg_password, reg_dob])
    except sqlite3.IntegrityError:
        flash('Such user already exists.')
    else:
        flash('A new user successfully created.')
    return redirect(url_for('show_index'))


@app.route('/create_match', methods=['POST'])
def create_match():
    username = session['username'] if 'username' in session else None
    player1_name = username if username else request.form['player1']
    player2_name = request.form['player2']
    venue_name = request.form['venue']
    try:
        best_of = int(request.form['bestof'])
    except ValueError:
        flash('Incorrect best of.')
        return redirect(url_for('show_index'))
    try:
        for row in con.execute('SELECT id FROM users WHERE username=?', [player1_name]):
            player1_id = row[0]
        print(player1_id)
        for row in con.execute('SELECT id FROM users WHERE username=?', [player2_name]):
            player2_id = row[0]
        print(player2_id)
        for row in con.execute('SELECT id FROM clubs WHERE clubname=?', [venue_name]):
            venue_id = row[0]
        print(venue_id)
    except UnboundLocalError:
        flash('Sorry, the players or club you selected do not exist.')
        return redirect(url_for('show_index'))
    try:
        for row in con.execute('SELECT id FROM matches WHERE player1=? AND player2=? AND club=? AND finished="false"', [player1_id, player2_id, venue_id]):
            match_id = row[0]
        print(match_id)
    except UnboundLocalError:
        with con:
            log = 'Log: ' + '2016-11-26 17:20:51' + ' : begin'
            con.execute('INSERT INTO matches(player1, player2, club, bestof, logs, p1_acc, p2_acc, club_acc, finished, date, p1_score, p2_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [player1_id, player2_id, venue_id, best_of, log, 'false', 'false', 'false', 'false', '2016-11-26 17:20:51', 0, 0])
            for row in con.execute('SELECT id FROM matches WHERE player1=? AND player2=? AND club=? AND finished="false"', [player1_id, player2_id, venue_id]):
                match_id = row[0]
            session['match_id'] = match_id
            flash('A new match created.')
            return redirect(url_for('show_scoreboard'))
    flash('Match loaded.')
    session['match_id'] = match_id
    return redirect(url_for('show_scoreboard'))


def parse_logs(logs):
    logs = logs.split('Log: ')[1:]
    print('Match logs before parsing at parse_logs:', logs)
    match_logs = []
    for log in logs:
        datetime, command = log.split(' : ')
        match_logs.append((datetime, command))
    print('Match logs at parse_logs:', match_logs)
    return match_logs


def get_scoring(match_logs, first_player):
    # XXX Heavily bugged. 1. Initial player not known.
    print('Match logs at get_scoring:', match_logs)
    for datetime, command in match_logs:
        if command == 'begin':
            scoring = {'time': [datetime], 'you': [0], 'opponent': [0]}
            if first_player == 'you':
                at_table = 'you'
                sitting = 'opponent'
        elif command == 'start':
            scoring['time'].append(datetime)
            scoring[at_table].append(0)
            scoring[sitting].append(0)
        elif command in ['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7']:
            scoring['time'].append(datetime)
            scoring[at_table].append(scoring[at_table][-1] + int(command[-1]))
            scoring[sitting].append(scoring[sitting][-1])
        elif command == 'change':
            scoring['time'].append(datetime)
            scoring[at_table].append(scoring[at_table][-1])
            scoring[sitting].append(scoring[sitting][-1])
            if at_table == 'you':
                at_table = 'opponent'
                sitting = 'you'
            else:
                at_table = 'you'
                sitting = 'opponent'
        elif command == 'win':
            scoring['time'].append(datetime)
            scoring[at_table].append(scoring[at_table][-1])
            scoring[sitting].append(scoring[sitting][-1])
        elif command in ['foul4', 'foul5', 'foul6', 'foul7']:
            scoring['time'].append(datetime)
            scoring[at_table].append(scoring[at_table][-1])
            scoring[sitting].append(scoring[sitting][-1] + int(command[-1]))
        print('At table:', scoring[at_table][-1], 'Sitting:', scoring[sitting][-1])
    return scoring


app.run(debug=True)
