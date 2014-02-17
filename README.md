#!/bin/bash
clear
HOMEDIR=/home/xbmc
MOVIESDB=NOFILE
DEST=/var/www

while [ ! -d $HOMEDIR ]; do
	echo "xbmcDB - '"$HOMEDIR"' doesn't exist!"
	echo -n "xbmcDB - Enter xbmc user: "
	read xdir
	HOMEDIR=/home/$xdir
done
echo "xbmcDB - homepath : '"$HOMEDIR"/'"

if [ $# -gt 0 ]; then
	MOVIESDB=$1

else
	#echo "xbmcDB - searching in '"$HOMEDIR"/.xbmc/userdata/Database/'..."
	for f in `ls $HOMEDIR/.xbmc/userdata/Database/ -t | grep .db | grep MyVideos`; do
		MOVIESDB=$f
		break;
	done
fi

if [ ! -f $HOMEDIR/.xbmc/userdata/Database/$MOVIESDB ]; then
	echo "xbmcDB - db-file was not found in '"$HOMEDIR"/.xbmc/userdata/Database/'!"
	echo "xbmcDB - run script with parameter, i.e. 'install_xbmcDB.sh MyVideosXX.db'!"
	echo "exiting!"
	exit 1
fi

echo "xbmcDB - db-file  : '"$HOMEDIR/.xbmc/userdata/Database/$MOVIESDB"'"
echo ""
echo "xbmcDB - '"$HOMEDIR"/.xbmc/userdata/Database/"$MOVIESDB"' will be moved to '/public/"$MOVIESDB"'!"
echo "         A symlink from homepath to '/public/' will be created!"
echo ""
echo -n "xbmcDB - Create symlink (yes, y), copy (no, n) or cancel (c)? "
read answer

if [ "$answer" != "yes" ] && [ "$answer" != "y" ] && [ "$answer" != "no" ] && [ "$answer" != "n" ]; then
	echo "xbmcDB - exiting"
	exit 1
fi

if [ ! -d /public ]; then
	sudo mkdir /public
	sudo chmod 0777 /public
fi

if [ "$answer" != "yes" ] && [ "$answer" != "y" ]; then
	cp -p $HOMEDIR/.xbmc/userdata/Database/$MOVIESDB /public/$MOVIESDB

else
	if [ -f $HOMEDIR/.xbmc/userdata/Database/$MOVIESDB ]; then
		cp -p $HOMEDIR/.xbmc/userdata/Database/$MOVIESDB /public/$MOVIESDB.bak
		mv $HOMEDIR/.xbmc/userdata/Database/$MOVIESDB /public/$MOVIESDB
		if [ -f /public/$MOVIESDB ]; then
			ln -s /public/$MOVIESDB $HOMEDIR/.xbmc/userdata/Database/$MOVIESDB
		fi
		chmod 0666 /public/$MOVIESDB
	fi
fi

if [ ! -d $DEST ]; then
	mkdir $DEST
	chmod 0777 $DEST
fi

sudo apt-get update
sudo apt-get install -y git apache2 sqlite3 php5 php5-sqlite php5-gd php5-gmp

mv /var/www/ /var/www_/

git clone https://github.com/3dfx/xbmcDB.git $DEST/
#if [ ! -L $DEST/Thumbnails ]; then
#	ln -s $HOMEDIR/.xbmc/userdata/Thumbnails/ $DEST/Thumbnails
#	chmod 0777 $DEST/Thumbnails
#fi

sudo service apache2 restart

exit 0

