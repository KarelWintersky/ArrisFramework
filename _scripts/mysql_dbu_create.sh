#!/usr/bin/env bash

#
# Script to create MySQL db + user
#
# @author   Karel Wintersky <karel.wintersky@gmail.com>
# @version  0.2
# mysql_config_editor set --login-path=proftpd --host=localhost --user=proftpd --password


_bold=$(tput bold)
_underline=$(tput sgr 0 1)
_reset=$(tput sgr0)

_purple=$(tput setaf 171)
_red=$(tput setaf 1)
_green=$(tput setaf 76)
_tan=$(tput setaf 3)
_blue=$(tput setaf 38)

function _success()
{
    printf "${_green}✔ %s${_reset}\n" "$@"
}

function _printPoweredBy()
{
    echo ""
    echo "################################################################"
    echo "MySQL :: Create database, user and password"
    echo "(c) Karel Wintersky <karel.wintersky@gmail.com>, 2018"
    echo "################################################################"
    echo ""
}

function generatePassword()
{
    echo "$(openssl rand -base64 12)"
}

function getCredentials()
{
    read -e -p "Please enter the NAME of the new database! (example: database1): " DBNAME
    read -e -p "Please enter HOST for user access (remote access: '%'): " -i "localhost" USERHOST
    read -e -p "Please enter the database CHARACTER SET! (latin1, utf8, ...): " -i "utf8" CHARSET
    read -e -p "Please enter the NAME of the user (example: $DBNAME): " -i "$DBNAME" USERNAME
    read -e -p "Please enter the PASSWORD for the new database user! (current is $PASSWORD): " -i "$PASSWORD" PASSWORD
}

function getRootPassword()
{
    read -e -p "Please enter MySQL root user password: " ROOTPASSWORD

    ROOTACCESS="--user=root --password=${ROOTPASSWORD}"
}

function checkDBExist()
{
    local FOUND
    FOUND=`mysql ${ROOTACCESS} -e "SHOW DATABASES LIKE '${DBNAME}';" | grep ${DBNAME}`

    echo ${FOUND}

    #if [ "${FOUND}" = "${DBNAME}" ]; then
    #    echo "1"
    #else
    #    echo "0"
    #fi
}

function checkUserExist()
{
    local FOUND
    FOUND=`mysql ${ROOTACCESS} -e "SELECT COUNT(*) FROM mysql.user WHERE user = '${USERNAME}';" | grep 1`

    if [ "${FOUND}" = "1" ]; then
        echo "1"
    else
        echo "0"
    fi
}

function create()
{
    if [ ! -f ~/.my.cnf ]; then
        getRootPassword
    fi

    if [[ -n $(checkDBExist) ]]; then
        echo "${_purple}✔${_reset} Database ${DBNAME} already exist!";
    else
        echo "Creating database..."
        mysql ${ROOTACCESS} -e "CREATE DATABASE ${DBNAME} /*\!40100 DEFAULT CHARACTER SET ${CHARSET} */;"
        echo "${_green}✔${_reset} Database successfully created!"
    fi

    echo ""

    if [ $(checkUserExist) = "0" ]; then
        echo "Creating new user..."
        mysql ${ROOTACCESS} -e "CREATE USER ${USERNAME}@'${USERHOST}' IDENTIFIED BY '${PASSWORD}';"
        echo "${_green}✔${_reset} User successfully created!"
    else
        echo "${_purple}✔${_reset} User ${USERNAME} already exist!";
    fi

    echo ""

    echo "Granting ALL privileges on ${DBNAME} to ${USERNAME}!"
    mysql ${ROOTACCESS} -e "GRANT ALL PRIVILEGES ON ${DBNAME}.* TO '${USERNAME}'@'${USERHOST}';"
    mysql ${ROOTACCESS} -e "FLUSH PRIVILEGES;"
    echo ""
}

function printSuccessMessage()
{
    _success "MySQL DB / User creation completed!"
    echo ""

    echo "################################################################"
    echo ""
    echo " >> Database  : ${DBNAME}"
    echo " >> User      : ${USERNAME}"
    echo " >> Pass      : ${PASSWORD}"
    echo " >> Host      : ${USERHOST}"
    echo ""
    echo "################################################################"
}

################################################################################
# Main
################################################################################
export LC_CTYPE=C
export LANG=C
VERSION="0.2"

BIN_MYSQL=$(which mysql)

ROOTACCESS=
ROOTPASSWORD=
CHARSET='utf8';
DBNAME=
USERNAME=
USERHOST='localhost'
PASSWORD=$(generatePassword);

function main()
{
    _printPoweredBy

    getCredentials

    echo ""
    echo "################################################################"
    echo ""

    create
    printSuccessMessage
}

main

exit 0