#!/bin/bash
#
# ownCloud
#
# @author Thomas Müller
# @copyright 2014Thomas Müller thomas.mueller@tmit.eu
#

DATABASENAME=oc_autotest
DATABASEUSER=oc_autotest
ADMINLOGIN=admin
BASEDIR=$PWD

#DBCONFIGS="sqlite mysql pgsql oci"
DBCONFIGS="sqlite mysql pgsql"
PHPUNIT=$(which phpunit)

if [ $1 ]; then
	FOUND=0
	for DBCONFIG in $DBCONFIGS; do
		if [ $1 = $DBCONFIG ]; then
			FOUND=1
			break
		fi
	done
	if [ $FOUND = 0 ]; then
		echo -e "Unknown database config name \"$1\"\n" >&2
		print_syntax
		exit 2
	fi
fi

# use tmpfs for datadir - should speedup unit test execution
DATADIR=$BASEDIR/data-autotest

echo "Using database $DATABASENAME"

# create autoconfig for sqlite, mysql and postgresql
cat > ./tests/autoconfig-sqlite.php <<DELIM
<?php
\$AUTOCONFIG = array (
  'installed' => false,
  'dbtype' => 'sqlite',
  'dbtableprefix' => 'oc_',
  'adminlogin' => '$ADMINLOGIN',
  'adminpass' => 'admin',
  'directory' => '$DATADIR',
);
DELIM

cat > ./tests/autoconfig-mysql.php <<DELIM
<?php
\$AUTOCONFIG = array (
  'installed' => false,
  'dbtype' => 'mysql',
  'dbtableprefix' => 'oc_',
  'adminlogin' => '$ADMINLOGIN',
  'adminpass' => 'admin',
  'directory' => '$DATADIR',
  'dbuser' => '$DATABASEUSER',
  'dbname' => '$DATABASENAME',
  'dbhost' => 'localhost',
  'dbpass' => 'owncloud',
);
DELIM

cat > ./tests/autoconfig-pgsql.php <<DELIM
<?php
\$AUTOCONFIG = array (
  'installed' => false,
  'dbtype' => 'pgsql',
  'dbtableprefix' => 'oc_',
  'adminlogin' => '$ADMINLOGIN',
  'adminpass' => 'admin',
  'directory' => '$DATADIR',
  'dbuser' => '$DATABASEUSER',
  'dbname' => '$DATABASENAME',
  'dbhost' => 'localhost',
  'dbpass' => '',
);
DELIM

cat > ./tests/autoconfig-oci.php <<DELIM
<?php
\$AUTOCONFIG = array (
  'installed' => false,
  'dbtype' => 'oci',
  'dbtableprefix' => 'oc_',
  'adminlogin' => '$ADMINLOGIN',
  'adminpass' => 'admin',
  'directory' => '$DATADIR',
  'dbuser' => '$DATABASENAME',
  'dbname' => 'XE',
  'dbhost' => 'localhost',
  'dbpass' => 'owncloud',
);
DELIM

function execute_tests {
	echo "Setup environment for $1 testing ..."
	# back to root folder
	cd $BASEDIR

	# revert changes to tests/data
	git checkout tests/data/*

	# reset data directory
	rm -rf $DATADIR
	mkdir $DATADIR

	# remove the old config file
	#rm -rf config/config.php
	cp tests/preseed-config.php config/config.php

	# drop database
	if [ "$1" == "mysql" ] ; then
		mysql -u $DATABASEUSER -powncloud -e "DROP DATABASE $DATABASENAME"
	fi
	if [ "$1" == "pgsql" ] ; then
		dropdb -U $DATABASEUSER $DATABASENAME
	fi
	if [ "$1" == "oci" ] ; then
		echo "drop the database"
		sqlplus -s -l / as sysdba <<EOF
			drop user $DATABASENAME cascade;
EOF

		echo "create the database"
		sqlplus -s -l / as sysdba <<EOF
			create user $DATABASENAME identified by owncloud;
			alter user $DATABASENAME default tablespace users
			temporary tablespace temp
			quota unlimited on users;
			grant create session
			, create table
			, create procedure
			, create sequence
			, create trigger
			, create view
			, create synonym
			, alter session
			to $DATABASENAME;
			exit;
EOF
	fi

	# copy autoconfig
	cp $BASEDIR/tests/autoconfig-$1.php $BASEDIR/config/autoconfig.php

	# trigger installation
	echo "INDEX"
	php -f index.php | grep -i -C9999 error && echo "Error during setup" && exit 101
	echo "END INDEX"

	#test execution
	cd tests
	php -f enable_all.php | grep -i -C9999 error && echo "Error during setup" && exit 101
}

#
# start test execution
#
if [ -z "$1" ]
  then
	# run all known database configs
	for DBCONFIG in $DBCONFIGS; do
		execute_tests $DBCONFIG
	done
else
	execute_tests $1 $2 $3
fi

cd $BASEDIR

