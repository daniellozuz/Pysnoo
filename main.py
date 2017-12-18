from flask import Flask, render_template, session, request, redirect, url_for
import sqlite3


app = Flask(__name__)
app.secret_key = 'dupa' # XXX What is it for and how does it work?


@app.route('/')
def main_page():
    username = session['username'] if 'username' in session else None
    return render_template('index.html', username=username)


@app.route('/logout', methods=['POST'])
def log_out():
    del session['username'] # XXX Not safe?
    return redirect(url_for('main_page'))


@app.route('/login', methods=['POST'])
def log_in():
    conn = sqlite3.connect('snooker.db')
    c = conn.cursor()
    for row in c.execute('SELECT * FROM users'):
        print(row)
    username = request.form['username'] # XXX Not safe?
    password = request.form['password'] # XXX Not safe?
    if username and password == 'OK': # TODO Check for account in db and set session accordingly.
        session['username'] = username
    return redirect(url_for('main_page'))


app.run(debug=True)
