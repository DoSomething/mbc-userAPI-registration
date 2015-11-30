#!/bin/bash
##
# Installation script for mbc-userAPI-registration
##

# Assume messagebroker-config repo is one directory up
cd ../messagebroker-config

# Gather path from root
MBCONFIG=`pwd`

# Back to mbc-userAPI-registration
cd ../mbc-userAPI-registration

# Create SymLink for mbc-userAPI-registration application to make reference to
# for all Message Broker configuration settings
ln -s $MBCONFIG .
