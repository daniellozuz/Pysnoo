class Scoring(object):
    '''Provides general and detailed statistics and scoreboard.'''


    def __init__(self, con, username, user_id=None, match_id=None):
        self.con = con
        self.username = username
        self.match_id = match_id
        self.match_logs = None
        self.user_id = user_id
        if match_id:
            self.match_logs = self._get_match_logs()


    def _get_match_logs(self):
        '''Retrurns a list of logs in form: [(time1, command1), (time2, command2), ...].'''
        match_logs = self.con.execute('SELECT logs '
                                      'FROM matches '
                                      'WHERE id=?',
                                      [self.match_id]).fetchone()[0]
        return [log.split(' : ') for log in match_logs.split('Log: ')[1:]]


    @property
    def generic_stats(self):
        matches = []
        for row in self.con.execute('SELECT IFNULL(B.username,"") player1, '
                                    'IFNULL(C.username,"") player2, '
                                    'IFNULL(D.clubname,"") clubname, '
                                    'A.id, A.bestof, A.date, A.p1_score, A.p2_score FROM matches A '
                                    'LEFT JOIN users B ON A.player1 = B.id '
                                    'LEFT JOIN users C ON A.player2 = C.id '
                                    'LEFT JOIN clubs D ON A.club = D.id '
                                    'WHERE (player1=? OR player2=?) AND finished="true"',
                                    [self.user_id, self.user_id]):
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
            if self.username == match['player1']:
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
        return matches, won, lost


    @property
    def detailed_stats(self):
        player1_id, match_logs = self.con.execute('SELECT player1, logs '
                                                  'FROM matches '
                                                  'WHERE id=?',
                                                  [self.match_id]).fetchone()
        player1 = 'you' if player1_id == self.user_id else 'opponent'
        match_logs = [log.split(' : ') for log in match_logs.split('Log: ')[1:]]
        # XXX Check which player are you. XXX
        if player1 == 'you':
            at_table = 'you'
            sitting = 'opponent'
        else:
            at_table = 'opponent'
            sitting = 'you'
        scores = {'time': [], 'you': [], 'opponent': []}
        for time, command in match_logs:
            scores['time'].append(time)
            if command == 'begin':
                scores[at_table].append(0)
                scores[sitting].append(0)
            elif command == 'start':
                scores[at_table].append(0)
                scores[sitting].append(0)
            elif command in ['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7']:
                scores[at_table].append(scores[at_table][-1] + int(command[-1]))
                scores[sitting].append(scores[sitting][-1])
            elif command == 'change':
                scores[at_table].append(scores[at_table][-1])
                scores[sitting].append(scores[sitting][-1])
                at_table, sitting = sitting, at_table
            elif command == 'win':
                scores[at_table].append(scores[at_table][-1])
                scores[sitting].append(scores[sitting][-1])
            elif command in ['foul4', 'foul5', 'foul6', 'foul7']:
                scores[at_table].append(scores[at_table][-1])
                scores[sitting].append(scores[sitting][-1] + int(command[-1]))
        return scores


    @property
    def scoreboard_info(self):
        player1, player2, info = {}, {}, {}

        info['paused'] = False
        info['break'] = 11
        info['shot_time'] = 28
        info['frame_time'] = 577
        info['best_of'] = 7
        info['club'] = 'Frame'
        info['player1_at_table'] = True

        player1['name'] = 'Danio'
        player1['points'] = 53
        player1['frames'] = 2

        player2['name'] = 'Drugi koles'
        player2['points'] = 24
        player2['frames'] = 4

        return player1, player2, info


if __name__ == '__main__':
    import sqlite3
    con = sqlite3.connect('snooker.db', check_same_thread=False)
    scoring = Scoring(con, 'Daniel Zuziak', user_id=1, match_id=40)
    print(scoring.match_logs)
    print(scoring.generic_stats)
