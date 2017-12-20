class Scoring(object):
    '''Provides general and detailed statistics and scoreboard.'''


    def __init__(self, con, username, user_id=None, match_id=None):
        self.con = con
        self.username = username
        self.match_id = match_id
        self.user_id = user_id
        self.info = {}
        self.player1 = {}
        self.player2 = {}


    @property
    def _match_logs(self):
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
        club_id = self.con.execute('SELECT club '
                                   'FROM matches '
                                   'WHERE id=?',
                                   [self.match_id]).fetchone()[0]
        self.info['club'] = self.con.execute('SELECT clubname '
                                             'FROM clubs '
                                             'WHERE id=?',
                                             [club_id]).fetchone()[0]
        player1_id = self.con.execute('SELECT player1 '
                                      'FROM matches '
                                      'WHERE id=?',
                                      [self.match_id]).fetchone()[0]
        self.player1['name'] = self.con.execute('SELECT username '
                                                'FROM users '
                                                'WHERE id=?',
                                                [player1_id]).fetchone()[0]
        player2_id = self.con.execute('SELECT player2 '
                                      'FROM matches '
                                      'WHERE id=?',
                                      [self.match_id]).fetchone()[0]
        self.player2['name'] = self.con.execute('SELECT username '
                                                'FROM users '
                                                'WHERE id=?',
                                                [player2_id]).fetchone()[0]
        self.info['best_of'] = int(self.con.execute('SELECT bestof '
                                                    'FROM matches '
                                                    'WHERE id=?',
                                                    [self.match_id]).fetchone()[0])
        # TODO change state into enums?
        # STATES = ['normal', 'paused', 'just_begun', 'just_won', 'finished']

        for time, command in self._match_logs:
            print(time, command)
            if command == 'begin':
                self.info['active_player']= 'player1'
                self.info['state'] = 'just_begun'
                self.info['break'] = 0
                self.info['shot_time'] = 0
                self.info['frame_time'] = 0
                self.player1['points'] = 0
                self.player1['frames'] = 0
                self.player2['points'] = 0
                self.player2['frames'] = 0
            elif command == 'start':
                self.info['state'] = 'normal'
            elif command == 'pause':
                self.info['state'] = 'paused'
            elif command == 'resume':
                self.info['state'] = 'normal'
            elif command == 'change':
                self._change_active_player()
            elif command == 'begin':
                self.info['state'] = 'just_begun'
            elif command in ['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7']:
                self._add_points_to_active_player(int(command[-1]))
            elif command in ['foul4', 'foul5', 'foul6', 'foul7']:
                self._change_active_player()
                self._add_points_to_active_player(int(command[-1]))
                self.info['state'] = 'missable'
            elif command == 'win':
                self.player1['points'] = 0
                self.player2['points'] = 0
                self._add_frame_to_active_player()
                self._select_active_player()
                self.info['state'] = 'just_won'
                if self.player1['frames'] + self.player2['frames'] == self.info['best_of']:
                    self.info['state'] = 'finished'
        return self.player1, self.player2, self.info


    def _change_active_player(self):
        if self.info['active_player'] == 'player1':
            self.info['active_player'] = 'player2'
        else:
            self.info['active_player'] = 'player1'


    def _add_points_to_active_player(self, amount):
        if self.info['active_player'] == 'player1':
            self.player1['points'] += amount
        else:
            self.player2['points'] += amount


    def _add_frame_to_active_player(self):
        if self.info['active_player'] == 'player1':
            self.player1['frames'] += 1
        else:
            self.player2['frames'] += 1


    def _select_active_player(self):
        if (self.player1['frames'] + self.player2['frames']) % 2 == 0:
            self.info['active_player'] = 'player1'
        else:
            self.info['active_player'] = 'player2'
