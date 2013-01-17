#!/bin/sh

# this script generates DSA ssh keys for remote command execution
LOGIN=$1
KEY_FILENAME=dsakey.${LOGIN}

ssh-keygen -t dsa -C "Ubilling" -f ${KEY_FILENAME} 
