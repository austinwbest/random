import qbittorrentapi
import subprocess
import json
from urllib.parse import urlparse

qbt_client = qbittorrentapi.Client(
    host='localhost',
    port=8080,
    username='',
    password='',
)

try:
    qbt_client.auth_log_in()
except qbittorrentapi.LoginFailed as e:
    print(e)

errors = {}
for torrent in qbt_client.torrents_info():
    trackers = qbt_client.torrents_trackers(torrent.hash)

    for tracker in trackers:
        if tracker.status == 4:
            torrentObject = {k: v for k, v in torrent.items()}
            torrentObject.pop('magnet_uri', None)
            torrentObject.pop('content_path', None)
            torrentObject['tracker'] = torrentObject['tracker'].rsplit('/', 1)[0]

            realTrackers = []
            realTrackerItem = {}
            for trackerItem in trackers:
                if '*' not in trackerItem['url'] and trackerItem['msg']:
                    realTrackerItem['url'] = trackerItem['url'].rsplit('/', 1)[0]
                    realTrackerItem['msg'] = trackerItem['msg']
                    realTrackerItem['num_peers'] = trackerItem['msg']
                    realTrackers.append(realTrackerItem)

            errors[torrent.hash[-6:]] = {'torrent': torrentObject, 'trackers': realTrackers}
            break

for hash, item in errors.items():
    print(item)
    inlineFields    = []
    inlineField     = {}
    singleFields    = []
    singleField     = {}

    inlineField['Hash'] = hash

    if item['torrent']['tags']:
        inlineField['Tags'] = item['torrent']['tags']

    if item['torrent']['category']:
        inlineField['Category'] = item['torrent']['category']

    singleField['Name'] = item['torrent']['name']

    urls = ''
    urlErrors = ''
    for tracker in item['trackers']:
        url = urlparse(tracker['url'])
        urls += url.scheme + '://' + url.netloc + "\n";
        urlErrors += tracker['msg']

    singleField['URL'] = '```' + urls + '```'
    singleField['Error'] = '```' + urlErrors + '```'

    inlineFields.append(inlineField)
    singleFields.append(singleField)

    webhook = "python notifiarr.py -e \"QBT Tracker Error\" -c 631827062348578827 -m \"FFA500\" -t \"Tracker Error\" -b \"Tracker URLs found with errors\" -g \"" + json.dumps(inlineFields).replace('"', r'\"') + "\" -f \"" + json.dumps(singleFields).replace('"', r'\"') + "\" -a \"https://notifiarr.com/images/logo/notifiarr.png\" -z \"Passthrough Integration\""
    subprocess.call(webhook)
