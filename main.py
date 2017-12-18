from flask import Flask, render_template, session, request, redirect, url_for, flash
import sqlite3


app = Flask(__name__)
app.secret_key = 'dupa' # XXX What is it for and how does it work?

conn = sqlite3.connect('snooker.db', check_same_thread=False)


@app.route('/index.html')
def show_index():
    username = session['username'] if 'username' in session else None
    return render_template('index.html', username=username)


@app.route('/profile.html')
def show_profile():
    username = None
    if 'username' in session:
        for row in conn.execute('SELECT * FROM users WHERE username=?', [session['username']]):
            user_id, username, password, date_of_birth = row
    return render_template('profile.html', user_id=user_id, username=username, password=password,
                           date_of_birth=date_of_birth)


@app.route('/register.html')
def show_register():
    username = session['username'] if 'username' in session else None
    return render_template('register.html', username=username)


@app.route('/login', methods=['POST'])
def log_in():
    username = request.form['username'] # XXX Not safe?
    password = request.form['password'] # XXX Not safe?
    print(conn.execute('SELECT * FROM users WHERE username=?', [username]).fetchall())
    for row in conn.execute('SELECT * FROM users WHERE username=?', [username]):
        if row[2] == password:
            session['username'] = username
    return redirect(url_for('show_index'))


@app.route('/logout', methods=['POST'])
def log_out():
    del session['username'] # XXX Not safe?
    return redirect(url_for('main_page'))


@app.route('/register', methods=['POST'])
def register():
    # XXX Check if data is valid
    reg_name = request.form['reg_name']
    reg_surname = request.form['reg_surname']
    reg_password = request.form['reg_password']
    reg_dob = request.form['reg_dob']
    reg_username = reg_name + ' ' + reg_surname
    try:
        with conn:
            conn.execute('INSERT INTO users(username, password, date_of_birth) VALUES (?, ?, ?)',
                         [reg_username, reg_password, reg_dob])
    except sqlite3.IntegrityError:
        flash('Such user already exists.')
    else:
        flash('A new user successfully created.')
    return redirect(url_for('show_index'))




app.run(debug=True)
