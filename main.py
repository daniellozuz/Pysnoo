import datetime
from flask import Flask, render_template, session, request, redirect, url_for, flash
import sqlite3
from Scoring import Scoring


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
        user_id, username, password, date_of_birth = con.execute('SELECT * '
                                                                 'FROM users '
                                                                 'WHERE username=?',
                                                                 [session['username']]).fetchone()
    return render_template('profile.html', user_id=user_id, username=username,
                           password=password, date_of_birth=date_of_birth)


@app.route('/register.html')
def show_register():
    username = session['username'] if 'username' in session else None
    return render_template('register.html', username=username)


@app.route('/generic_stats.html')
def show_generic_stats():
    # XXX What if not logged in?
    username = session['username'] if 'username' in session else None
    user_id = session['id'] if 'id' in session else None
    scoring = Scoring(con, username, user_id=user_id)
    matches, won, lost = scoring.generic_stats
    return render_template('generic_stats.html', username=username,
                           matches=matches, won=won, lost=lost)


@app.route('/detailed_stats.html', methods=['POST'])
def show_detailed_stats():
    match_id = request.form['show_match']
    username = session['username'] if 'username' in session else None
    scoring = Scoring(con, username, match_id=match_id)
    scores = scoring.detailed_stats
    username = session['username'] if 'username' in session else None
    return render_template('detailed_stats.html', username=username, scores=scores)


@app.route('/create_match.html')
def show_create_match():
    if 'match_id' in session:
        return redirect(url_for('show_scoreboard'))
    players = [row[1] for row in con.execute('SELECT * '
                                             'FROM users '
                                             'ORDER BY username ASC')]
    clubs = [row[1] for row in con.execute('SELECT * '
                                           'FROM clubs '
                                           'ORDER BY clubname ASC')]
    username = session['username'] if 'username' in session else None
    return render_template('create_match.html', username=username, players=players, clubs=clubs)


@app.route('/scoreboard.html')
def show_scoreboard():
    # XXX I do not have to be logged in in order to play matches.
    # Show match which id is in session['match_id]
    match_id = session['match_id']
    username = session['username']
    user_id = session['id']
    scoring = Scoring(con, username, user_id=user_id, match_id=match_id)
    player1, player2, info = scoring.scoreboard_info
    username = session['username'] if 'username' in session else None
    return render_template('scoreboard.html', username=username,
                           player1=player1, player2=player2, info=info)


@app.route('/login', methods=['POST'])
def form_log_in():
    # XXX Validate form.
    username = request.form['username']
    password = request.form['password']
    for row in con.execute('SELECT * FROM users WHERE username=?', [username]):
        if row[2] == password:
            session['username'] = username
            session['id'] = row[0]
    return redirect(url_for('show_index'))


@app.route('/logout', methods=['POST'])
def form_log_out():
    del session['username']
    return redirect(url_for('show_index'))


@app.route('/register', methods=['POST'])
def form_register():
    # XXX Validate form.
    reg_name = request.form['reg_name']
    reg_surname = request.form['reg_surname']
    reg_password = request.form['reg_password']
    reg_dob = request.form['reg_dob']
    reg_username = reg_name + ' ' + reg_surname
    try:
        with con:
            con.execute('INSERT INTO users(username, password, date_of_birth) '
                        'VALUES (?, ?, ?)',
                        [reg_username, reg_password, reg_dob])
    except sqlite3.IntegrityError:
        flash('Such user already exists.')
    else:
        flash('A new user successfully created.')
    return redirect(url_for('show_index'))


@app.route('/create_match', methods=['POST'])
def form_create_match():
    username = session['username'] if 'username' in session else None
    player1, player2, club = {}, {}, {}
    player1['name'] = username if username else request.form['player1']
    player2['name'] = request.form['player2']
    club['name'] = request.form['club']
    try:
        best_of = int(request.form['bestof'])
    except ValueError:
        flash('Incorrect best of.')
        return redirect(url_for('show_index'))
    try:
        player1['id'] = con.execute('SELECT id '
                                    'FROM users '
                                    'WHERE username=?',
                                    [player1['name']]).fetchone()[0]
        player2['id'] = con.execute('SELECT id '
                                    'FROM users '
                                    'WHERE username=?',
                                    [player2['name']]).fetchone()[0]
        club['id'] = con.execute('SELECT id '
                                 'FROM clubs '
                                 'WHERE clubname=?',
                                 [club['name']]).fetchone()[0]
    except TypeError:
        flash('Sorry, the players or club you selected do not exist.')
        return redirect(url_for('show_index'))
    try:
        session['match_id'] = con.execute('SELECT id '
                                          'FROM matches '
                                          'WHERE player1=? '
                                          'AND player2=? '
                                          'AND club=? '
                                          'AND finished="false"',
                                          [player1['id'], player2['id'], club['id']]).fetchone()[0]
        flash('Match loaded.')
    except TypeError:
        with con:
            time = str(datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
            initial_log = 'Log: ' + time + ' : begin'
            con.execute('INSERT INTO matches(player1, player2, club, bestof, logs, p1_acc, p2_acc, '
                        'club_acc, finished, date, p1_score, p2_score) '
                        'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [player1['id'], player2['id'], club['id'], best_of, initial_log,
                         'false', 'false', 'false', 'false', time, 0, 0])
        session['match_id'] = con.execute('SELECT id '
                                          'FROM matches '
                                          'WHERE player1=? '
                                          'AND player2=? '
                                          'AND club=? '
                                          'AND finished="false"',
                                          [player1['id'], player2['id'],
                                           club['id']]).fetchone()[0]
        flash('A new match created.')
    return redirect(url_for('show_scoreboard'))


@app.route('/scoreboard', methods=['POST'])
def form_scoreboard():
    if len(list(request.form.keys())) == 1:
        button = list(request.form.keys())[0]
    else:
        button = request.form['finish'].lower()
    if button == 'GoToMatch_Back':
        del session['match_id']
    elif button == 'back':
        previous_logs = con.execute('SELECT logs '
                                    'FROM matches '
                                    'WHERE id=?',
                                    [session['match_id']]).fetchone()[0]
        with con:
            con.execute('UPDATE matches '
                        'SET logs=? '
                        'WHERE id=?',
                        [previous_logs.rpartition('Log: ')[0], session['match_id']])
    else:
        time = str(datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
        new_log = 'Log: ' + time + ' : ' + button
        previous_logs = con.execute('SELECT logs '
                                    'FROM matches '
                                    'WHERE id=?',
                                    [session['match_id']]).fetchone()[0]
        with con:
            con.execute('UPDATE matches '
                        'SET logs=? '
                        'WHERE id=?',
                        [previous_logs + new_log, session['match_id']])
        if button == 'finish':
            with con:
                con.execute('UPDATE matches '
                            'SET finished=?, p1_score=?, p2_score=? '
                            'WHERE id=?',
                            ['true', request.form['player1_final_score'],
                             request.form['player2_final_score'], session['match_id']])
            del session['match_id']
    return redirect(url_for('show_create_match'))


if __name__ == '__main__':
    app.run(debug=True)
