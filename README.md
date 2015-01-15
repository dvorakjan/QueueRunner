# QueueRunner #
Simple daemon for running tasks/scripts from mongo queues.

## Installing ##
Install project using
```
composer create-project vojtabiberle/QueueRunner
```
Copy stuffs from support/etc to etc and settup everything (simple ini file and systemd target).

Add systemd target to running after boot.

## Future plans ##
- Adding better support for more DB adapters
- Adding support for jeremeamia/super_closure - can run super_closure at background and plann it