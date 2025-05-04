# Systemadmin module
---
## What is the Systemadmin module?

Warning: The module is still in an early alpha version. Please do not install it on production machines, but only on development machines for testing/debugging.

Systemadmin is a free open source module for any FreePBX® 17 based systems (e.g. FreePBX, TangoPBX and IncrediblePBX). With this module you can change system settings of the PBX including:

* Network settings (IP assignment method, IP address etc)
* DNS settings
* Hostname/Domainname
* eMail server settings (wheter sending emails through the local or through a remote MTA).

This is the module for the webinterface of you FreePBX based PBX.

## Module License

This module is published under GNU AGPL 3.0

## Module Requirements

Systemadmin requires FreePBX, IncrediblePBX or TangoPBX 17. Before you can install this module, please install the required binaries. Installation instructions for the binaries can be found [Here](https://github.com/hannes427/sysadmin "Systemadmin binaries").

## How Do I install this Module?
First you need to Upload the module to your Test-PBX. To do so,please log into the webinterface of you Test-PBX and browse to Admin-> Module Admin-> Upload modules and enter the url https://github.com/hannes427/systemadmin_php/raw/refs/heads/master/install/systemadmin.tar.gz in the field.

After hitting the button Download (From web) you need to install the module. In the web interface of the test PBX, navigate to Admin -> Module Admin for the second time. In the overview of all modules, click on the module "System Admin" and then press the buttons "Install" and "Process."
