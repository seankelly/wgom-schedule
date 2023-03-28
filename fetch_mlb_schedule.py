#!/usr/bin/env python3

import argparse
import csv
import sys
from datetime import datetime
from zoneinfo import ZoneInfo
from typing import Dict, List, Tuple, Union

import requests


SCHEDULE_API = 'https://statsapi.mlb.com/api/v1/schedule?lang=en&sportId=1&hydrate=team(venue(timezone)),game,broadcasts(tv)&season={year}&startDate={start_date}&endDate={end_date}&teamId={team}&eventTypes=primary&scheduleTypes=games'

TEAM_IDS = {
    'MIN': 142,
}


class Schedule:

    def __init__(self, team_or_id: Union[str | int], start: str, end: str) -> None:
        if isinstance(team_or_id, str):
            team = TEAM_IDS.get(team_or_id.upper())
            if team is None:
                print(f"Could not find team name '{team_or_id}'")
                sys.exit(1)
        elif isinstance(team_or_id, int):
            team = team_or_id
        else:
            raise RuntimeError(f"Unknown team type: {team_or_id}")
        self.team = team

        year = start.split('-', 1)[0]
        self.options = {
            'start_date': start,
            'end_date': end,
            'team': team,
            'year': year,
        }
        self.games = []

    def fetch(self) -> Dict:
        schedule_url = SCHEDULE_API.format(**self.options)
        #print(f"{schedule_url=}")
        res = requests.get(schedule_url)
        res.raise_for_status()
        stats_sched = res.json()
        #print(f"{stats_sched=}")
        return stats_sched

    # Schedule format:
    #  YYYY-MM-DD,HH:MM,Opponent,Home,TV channels
    def schedule(self) -> List[Tuple]:
        sched = self.fetch()
        games = []
        for date in sched['dates']:
            for game in date['games']:
                official_date = game['officialDate']
                game_date = datetime.strptime(game['gameDate'], '%Y-%m-%dT%H:%M:%S%z')
                home = self._extract_team(game['teams']['home'])
                away = self._extract_team(game['teams']['away'])

                if home['id'] == self.team:
                    team = home
                    opponent = away
                    broadcast = 'home'
                    home_status = 'H'
                else:
                    team = away
                    opponent = home
                    broadcast = 'away'
                    home_status = 'A'

                zone = ZoneInfo(team['tz'])
                local_date = game_date.astimezone(zone)
                tv_info = self._extract_broadcasts(game['broadcasts'], broadcast)
                game_info = (
                    local_date.strftime('%Y-%m-%d'),
                    local_date.strftime('%H:%M'),
                    opponent['abbreviation'],
                    home_status,
                    ' '.join(sorted(tv_info)),
                )
                games.append(game_info)
        self.games = games
        return games

    @staticmethod
    def _extract_team(team_dict) -> Dict:
        team_info = {
            'id': team_dict['team']['id'],
            'name': team_dict['team']['name'],
            'abbreviation': team_dict['team']['abbreviation'],
            'venue': team_dict['team']['venue']['name'],
            'tz': team_dict['team']['venue']['timeZone']['id'],
        }
        return team_info

    @staticmethod
    def _extract_broadcasts(broadcasts, home_away, broadcast_type='TV') -> List[str]:
        callsigns = []
        for broadcast in broadcasts:
            if broadcast['type'] != broadcast_type:
                continue
            if broadcast['homeAway'] != home_away:
                continue
            callsigns.append(broadcast['callSign'])
        return callsigns


def options():
    parser = argparse.ArgumentParser()
    parser.add_argument('--start', '-s', help="First day of schedule to fetch")
    parser.add_argument('--end', '-e', help="Last day of schedule to fetch")
    parser.add_argument('--out', '-o', help="File to save the CSV schedule")
    parser.add_argument('team', help="Team schedule to fetch")
    args = parser.parse_args()
    return args


def main():
    args = options()
    today = datetime.today()
    start = args.start or today.strftime('%Y-%m-%d')
    end = args.end or start

    schedule = Schedule(args.team, start, end)
    games = schedule.schedule()
    output_file = args.out or f'{args.team}-schedule.csv'
    with open(output_file, 'w') as output:
        writer = csv.writer(output)
        writer.writerows(games)


if __name__ == '__main__':
    main()
