How to test the web hooks
=========================

1. Create a new RequestBin at http://requestb.in/

2. Fire a curl command to subscribe to the pubsubhubbub:

curl -u "user" -i \
  https://<owncloud-url>/index.php/apps/web_hooks/hub \
  -F "hub.mode=subscribe" \
  -F "hub.topic=owncloud://filesystem-change" \
  -F "hub.callback=http://requestb.in/123"

3. Change a file e.g. using the text editor

4. Fire the cron-job (in case of Ajax based cron processing - reload ownCloud web-app multiple times)

5. See the change information arrive on your RequestBin
