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


@app.route('/stats.html')
def show_stats():
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
        print(match)
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
    return render_template('stats.html', username=username, matches=matches, won=won, lost=lost)


@app.route('/login', methods=['POST'])
def log_in():
    # XXX Validate form.
    username = request.form['username']
    password = request.form['password']
    print(con.execute('SELECT * FROM users WHERE username=?', [username]).fetchall())
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


app.run(debug=True)
