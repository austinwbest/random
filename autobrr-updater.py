# pip install requests
# pip install packaging
# pip install psutil

import os
import shutil
import tempfile
import requests
import tarfile
import psutil
import subprocess
from packaging import version

print('Update check starting...');

autobrrURL              = 'http://localhost:7474'
autobrrApikey           = ''
autobrrInstallFolder    = 'C:\\ProgramData\\autobrr'

print('Connecting to autobrr on ' + autobrrURL + ' using apikey ' + autobrrApikey + '...')
autobrrConfig           = requests.get(autobrrURL + '/api/config?apikey=' + autobrrApikey)
autobrrConfigResponse   = autobrrConfig.json()
currentVersion          = autobrrConfigResponse['version']
print('Current autobrr version: ' + currentVersion)

releases = requests.get('https://api.github.com/repos/autobrr/autobrr/releases')
json = releases.json()

for release in json:
    releaseVersion = release['tag_name'].replace('v', '')
    print('Newest version of autobrr found: ' + releaseVersion)

    if version.parse(releaseVersion) > version.parse(currentVersion):
        print('Newest version is greater than current version, upgrade needed...')

        asset = 'https://github.com/autobrr/autobrr/releases/download/' + release['tag_name'] + '/autobrr_' + releaseVersion + '_windows_x86_64.tar.gz'
        print('Grabbing asset: ' + asset + '...');

        tmpFolder           = tempfile.gettempdir()
        autobrrTmpFolder    = os.path.join(tmpFolder, 'autobrr')
        
        if os.path.exists(autobrrTmpFolder):
            shutil.rmtree(tmpFolder + '\\autobrr')

        os.mkdir(autobrrTmpFolder)
        tmpFilename         = 'autobrr_' + releaseVersion + '_windows_x86_64.tar.gz'
        tmpFile             = tmpFolder + '\\autobrr\\' + tmpFilename

        print('Temp folder: ' + tmpFolder)
        print('Temp file: ' + tmpFilename)
        print('Using tmpfile to save: ' + tmpFile + '...')

        grabAsset = requests.get(asset)
        with open(tmpFile, 'wb') as a:
            print('Saving asset to tmpfile (' + grabAsset.headers['Content-length'] + ' B)...')
            a.write(grabAsset.content)

        print('Save finished, extracting asset...')
        shutil.unpack_archive(tmpFile, tmpFolder + '\\autobrr')
        print('Extracting finished, killing autobrr process...')

        process = 'autobrr.exe'
        for proc in psutil.process_iter():
            if proc.name() == process:
                proc.kill()
        print('Process killed, moving update files...')

        shutil.move(tmpFolder + '\\autobrr\\autobrr.exe', autobrrInstallFolder + '\\autobrr.exe')
        shutil.move(tmpFolder + '\\autobrr\\autobrrctl.exe', autobrrInstallFolder + '\\autobrrctl.exe')
        
        print('Files moved, restarting autobrr...')
        
        os.startfile(autobrrInstallFolder + '\\autobrr.exe')

        print('Autobrr restarted, cleaning up...')
        shutil.rmtree(tmpFolder + '\\autobrr')
        break

print('Update check complete.')
exit()
