# REQUIRED: pip install qbittorrent-api
import qbittorrentapi
import subprocess
import json
import time
from urllib.parse import urlparse

NOTIFIARR_PY = 'C:\\Users\\nitsua\\Desktop\\scripts\\notifiarr.py'

qbt_client = qbittorrentapi.Client(
    host = 'localhost',
    port = 8080,
    username = '',
    password = ''
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

            workingURL      = ''
            brokenURL       = ''
            brokenMsg       = ''
            realTrackers    = []
            realTrackerItem = {}

            # Check all urls, ignore if at least one works
            for trackerItem in trackers:
                if '*' not in trackerItem.url:
                    if not trackerItem.msg:
                        workingURL = trackerItem.url
                    else:
                        brokenURL = trackerItem.url
                        brokenMsg = trackerItem.msg

            if not workingURL and brokenURL:
                realTrackerItem['url'] = brokenURL.rsplit('/', 1)[0]
                realTrackerItem['msg'] = brokenMsg
                realTrackers.append(realTrackerItem)
                errors[torrent.hash[-6:]] = {'torrent': torrentObject, 'trackers': realTrackers}

counter = 1
totalErrors = len(errors)
for hash, item in errors.items():
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

    urls        = ''
    urlErrors   = ''
    for tracker in item['trackers']:
        url = urlparse(tracker['url'])
        urls += url.scheme + '://' + url.netloc + "\n"
        urlErrors += tracker['msg'] + "\n"

    singleField['URL'] = '```' + urls + '```'
    singleField['Error'] = '```' + urlErrors + '```'

    inlineFields.append(inlineField)
    singleFields.append(singleField)

    webhook = "python " + NOTIFIARR_PY + " -e \"QBT Tracker Error\" -c 631827062348578827 -m \"FFA500\" -t \"Tracker Error ("+ str(counter) +"/"+ str(totalErrors) +")\" -b \"Tracker URLs found with errors\" -g \"" + json.dumps(inlineFields).replace('"', r'\"') + "\" -f \"" + json.dumps(singleFields).replace('"', r'\"') + "\" -a \"https://notifiarr.com/images/logo/notifiarr.png\" -z \"Passthrough Integration\""
    print(webhook)
    subprocess.call(webhook)
    time.sleep(1)
    counter = counter + 1
