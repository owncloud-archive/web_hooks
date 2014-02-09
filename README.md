#Web Hooks - ownCloud goes PubSubHubbub

Build status: [![Build Status](https://travis-ci.org/owncloud/web_hooks.png?branch=master)](https://travis-ci.org/owncloud/web_hooks) |

Quality: [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/owncloud/web_hooks/badges/quality-score.png?s=9dfd4fe5f4b771561de13ac9d16bf24d4204cc52)](https://scrutinizer-ci.com/g/owncloud/web_hooks/)

Coverage: [![Code Coverage](https://scrutinizer-ci.com/g/owncloud/web_hooks/badges/coverage.png?s=a41d6a9cf571950d6146bff9825d1e6d59f17e75)](https://scrutinizer-ci.com/g/owncloud/web_hooks/)

How to test the web hooks
-------------------------

1. Create a new RequestBin at http://requestb.in/

2. Fire a curl command to subscribe to the pubsubhubbub:
  ```sh
  curl -u "user" -i \
    https://<owncloud-url>/index.php/apps/web_hooks/hub \
    -F "hub.mode=subscribe" \
    -F "hub.topic=owncloud://filesystem-change" \
    -F "hub.callback=http://requestb.in/123"
  ```

3. Change a file e.g. using the text editor

4. Fire the cron-job (in case of Ajax based cron processing - reload ownCloud web-app multiple times)

5. See the change information arrive on your RequestBin
