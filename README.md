# QueueRunner #
Simple daemon for running tasks/scripts from mongo queues.

## Installing ##
Install project using
```
composer create-project vojtabiberle/queue-runner
```
Copy stuffs from support/etc to etc and settup everything (simple ini file and systemd target).

Add systemd target to running after boot.

```
systemctl daemon-reload #to reload systemd - systemd will notice new target
systemctl start queuerunner #start service
systemctl enable queuerunner #start service after boot
```

## Future plans ##
- Adding better support for more DB adapters
- Adding support for jeremeamia/super_closure - can run super_closure at background and plann it