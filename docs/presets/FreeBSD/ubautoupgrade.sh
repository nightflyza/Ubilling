#!/bin/sh

#
# WARNING!
# backup manually whole directory billing at first time before running this updater
# something like cp -R billing /safe/path/billing_helpp
#

######################## CONFIG SECTION ########################

#dialog gnu-dialog or bsddialog
if [ -z "$DIALOG" ]; then
    if command -v dialog >/dev/null 2>&1; then
        DIALOG=/usr/bin/dialog
    elif command -v bsddialog >/dev/null 2>&1; then
        DIALOG=/usr/bin/bsddialog
    else
        echo "ERROR: no gnu-dialog or bsddialog is available."
        exit 1
    fi
fi

#fetch software
FETCH="/usr/bin/fetch"

#tar binary
TAR="/usr/bin/tar"

# path to your apache data dir
APACHE_DATA_PATH="/usr/local/www/apache24/data/"

# billing path
UBILLING_PATH="billing/"

#kill default admin account after update?
DEFADM_KILL="NO"

#use DN online detection?
DN_ONLINE_LINKING="YES"

#update log file
LOG_FILE="/var/log/ubillingupdate.log"

#temp directory path
TEMP_PATH="/tmp/"

#restore point dir
RESTORE_POINT="/${TEMP_PATH}ub_restore"

#defaults
UBILLING_RELEASE_URL="http://ubilling.net.ua/"
UBILLING_RELEASE_NAME="ub.tgz"

######################## INTERFACE SECTION ####################

if [ $# -ne 1 ]
then
#interactive mode
$DIALOG --title "Ubilling update" --msgbox "This wizard help you to update your Ubilling installation to the the latest stable or current development release" 10 40
clear
$DIALOG --menu "Choose a Ubilling release branch to which you want to update." 11 65 6 \
 	   	   STABLE "Ubilling latest stable release (recommended)"\
           MIRROR "Ubilling latest stable release mirror"\
 	   	   CURRENT "Ubilling current development snapshot"\
            2> /tmp/auprelease
clear

BRANCH=`cat /tmp/auprelease`
rm -fr /tmp/auprelease

#last chance to exit
$DIALOG --title "Check settings"   --yesno "Are all of these settings correct? \n \n Ubilling release: ${BRANCH}\n Kill default admin: ${DEFADM_KILL} \n Installation full path: ${APACHE_DATA_PATH}${UBILLING_PATH}\n" 9 70
AGREE=$?
clear

else
#getting branch from CLI 1st param in batch mode
BRANCH=$1
AGREE="0"
fi

case $BRANCH in
STABLE)
UBILLING_RELEASE_URL="http://ubilling.net.ua/"
UBILLING_RELEASE_NAME="ub.tgz"
;;

CURRENT)
UBILLING_RELEASE_URL="http://snaps.ubilling.net.ua/"
UBILLING_RELEASE_NAME="ub_current.tgz"
;;

MIRROR)
UBILLING_RELEASE_URL="http://mirror.ubilling.net.ua/"
UBILLING_RELEASE_NAME="ub.tgz"
;;
esac


######################## END OF CONFIG ########################

#checking billing directory availability
if [ -d ${APACHE_DATA_PATH}${UBILLING_PATH} ]
then

case $AGREE in
0)
echo "=== Start Ubilling auto update ==="
cd ${APACHE_DATA_PATH}${UBILLING_PATH}

echo "=== Downloading new release ==="
$FETCH ${UBILLING_RELEASE_URL}${UBILLING_RELEASE_NAME}

if [ -f ${UBILLING_RELEASE_NAME} ];
then

echo "=== Creating restore point ==="
mkdir ${RESTORE_POINT} 2> /dev/null
rm -fr ${RESTORE_POINT}/*
rm -rf ${TEMP_PATH}onusig_bak
rm -rf ${TEMP_PATH}photostorage_bak
rm -rf ${TEMP_PATH}sql_bak
rm -fr ${TEMP_PATH}us_htaccess

echo "=== Move new release to safe place ==="
cp -R ${UBILLING_RELEASE_NAME} ${RESTORE_POINT}/

echo "=== Backup current data ==="

mkdir ${RESTORE_POINT}/config
mkdir ${RESTORE_POINT}/content
mkdir ${RESTORE_POINT}/multinet
mkdir ${RESTORE_POINT}/userstats
mkdir ${RESTORE_POINT}/userstats/config


# backup of actual configs and administrators
mv ./content/documents/onusig ${TEMP_PATH}onusig_bak
mv ./content/documents/photostorage ${TEMP_PATH}photostorage_bak
mv ./content/backups/sql ${TEMP_PATH}sql_bak
cp ./userstats/.htaccess ${TEMP_PATH}us_htaccess 2> /dev/null

cp .htaccess ${RESTORE_POINT}/ 2> /dev/null
cp favicon.ico ${RESTORE_POINT}/ 2> /dev/null
cp remote_nas.conf ${RESTORE_POINT}/
cp -R ./multinet ${RESTORE_POINT}/
cp ./config/alter.ini ${RESTORE_POINT}/config/
cp ./config/billing.ini ${RESTORE_POINT}/config/
cp ./config/mysql.ini ${RESTORE_POINT}/config/
cp ./config/ymaps.ini ${RESTORE_POINT}/config/
cp ./config/config.ini ${RESTORE_POINT}/config/
cp -R ./config/dhcp ${RESTORE_POINT}/config/
cp -R ./content/users ${RESTORE_POINT}/content/
cp -R ./content/reports ${RESTORE_POINT}/content/
cp -R ./content/documents ${RESTORE_POINT}/content/
cp ./config/printcheck.tpl ${RESTORE_POINT}/config/
cp ./userstats/config/mysql.ini ${RESTORE_POINT}/userstats/config/
cp ./userstats/config/userstats.ini ${RESTORE_POINT}/userstats/config/
cp ./userstats/config/tariffmatrix.ini ${RESTORE_POINT}/userstats/config/

echo "=== Billing directory cleanup ==="
rm -fr ${APACHE_DATA_PATH}${UBILLING_PATH}/*

echo "=== Unpacking new release ==="
cp  -R ${RESTORE_POINT}/${UBILLING_RELEASE_NAME} ${APACHE_DATA_PATH}${UBILLING_PATH}/
echo `date` >> ${LOG_FILE}
echo "====================" >> ${LOG_FILE}
$TAR zxvf ${UBILLING_RELEASE_NAME} 2>> ${LOG_FILE}
rm -fr ${UBILLING_RELEASE_NAME}

echo "=== Restoring configs ==="
cp -R ${RESTORE_POINT}/* ./
rm -rf ./content/documents/onusig
mv ${TEMP_PATH}onusig_bak ./content/documents/onusig
rm -rf ./content/documents/photostorage
mv ${TEMP_PATH}photostorage_bak ./content/documents/photostorage
rm -rf ./content/backups/sql
mv ${TEMP_PATH}sql_bak ./content/backups/sql
cp -R ${TEMP_PATH}us_htaccess ./userstats/.htaccess 2> /dev/null
rm -fr ${UBILLING_RELEASE_NAME}
echo "deny from all" > ${RESTORE_POINT}/.htaccess

#kill default admin
case $DEFADM_KILL in
NO)
echo "=== Default admin account skipped ===";;
YES)
rm -fr ./content/users/admin
echo "=== Default admin account removed ===";;
esac

echo "=== Setting FS permissions ==="
chmod -R 777 content/ config/ multinet/ exports/ remote_nas.conf
chmod -R 777 userstats/config/

case $DN_ONLINE_LINKING in 
NO)
echo "=== No DN online ==";;
YES)
mkdir ${APACHE_DATA_PATH}${UBILLING_PATH}/content/dn
chmod 777 /etc/stargazer/dn ${APACHE_DATA_PATH}${UBILLING_PATH}/content/dn
echo "=== Linking True Online ===";;
esac

# Setting up autoupdate script
if [ -f ./docs/presets/FreeBSD/ubautoupgrade.sh ];
then
echo "=== Updating autoupdater ==="
cp -R ./docs/presets/FreeBSD/ubautoupgrade.sh /bin/
chmod a+x /bin/ubautoupgrade.sh
else
echo "Looks like this Ubilling release does not containing automatic upgrade preset"
fi

echo "=== Executing post-install API callback ==="
/bin/ubapi "autoupdatehook" 2>> ${LOG_FILE}

echo "=== Deleting restore poing ==="
rm -fr ${RESTORE_POINT}
rm -rf ${TEMP_PATH}onusig_bak
rm -rf ${TEMP_PATH}photostorage_bak
rm -rf ${TEMP_PATH}sql_bak

NEW_RELEASE=`cat RELEASE`
echo "SUCCESS: Ubilling update successfully completed. Now your installation release is: ${NEW_RELEASE}"

#release file not dowloaded
else
echo "ERROR: No new Ubilling release file found, update aborted"
fi

;;
1)
echo "Update has been canceled"
exit
;;
esac

else
echo "ERROR: Update has been aborted: wrong Ubilling directory"
fi