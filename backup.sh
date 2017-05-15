#!/bin/bash

DBUSER=root  # юзер базы данных
DBPASSWORD=root  # пароль
DBBASE=earth_prod # база данных
BACKUPFOLDER=/sites/projects/earth-backup  # куда сохранять бэкапы
PREFIX=earth  # если несколько серверов - используйте разные префиксы, чтобы не путаться
DATE=`date '+%Y-%m-%d'`
SITEFOLDER=/sites/projects/earth
EMAIL=icsmailby@gmail.com
OLD=7

# *****************************
# Очистка папки с бэкапом
# *****************************
echo "Удаление старых файлов:" > $BACKUPFOLDER/$DATE-result-$PREFIX.txt
echo "Удаление старых файлов."
find $BACKUPFOLDER -mtime $OLD -user jantao -delete -print >> $BACKUPFOLDER/$DATE-result-$PREFIX.txt

# *****************************
# Бэкап базы данных
# *****************************
echo "Бэкап базы данных." >> $BACKUPFOLDER/$DATE-result-$PREFIX.txt
echo "Бэкап базы данных."
cd $BACKUPFOLDER
mysqldump -u $DBUSER -p$DBPASSWORD $DBBASE > $DATE-sql-$PREFIX.sql
echo "Архивация базы данных." >> $BACKUPFOLDER/$DATE-result-$PREFIX.txt
echo "Архивация базы данных."
tar -cjf $DATE-sql-$PREFIX.tar.bz2 $DATE-sql-$PREFIX.sql
echo "Проверка базы данных:" >> $BACKUPFOLDER/$DATE-result-$PREFIX.txt
echo "Проверка базы данных."
tar -df $DATE-sql-$PREFIX.tar.bz2 $DATE-sql-$PREFIX.sql >> $DATE-result-$PREFIX.txt
rm $DATE-sql-$PREFIX.sql
echo "Проверка завершена." >> $BACKUPFOLDER/$DATE-result-$PREFIX.txt

# *****************************
# Бэкап файлов
# *****************************
cd $SITEFOLDER
echo "Архивация файлов." >> $BACKUPFOLDER/$DATE-result-$PREFIX.txt
echo "Архивация файлов."
tar -cjf $BACKUPFOLDER/$DATE-files-$PREFIX.tar.bz2 . --exclude=var/cache/* --exclude=var/logs/* --exclude=var/sessions/*
echo "Проверка файлов:" >> $BACKUPFOLDER/$DATE-result-$PREFIX.txt
echo "Проверка файлов."
tar -df $BACKUPFOLDER/$DATE-files-$PREFIX.tar.bz2 . --exclude=var/cache/* --exclude=var/logs/* --exclude=var/sessions/* >> $BACKUPFOLDER/$DATE-result-$PREFIX.txt
echo "Проверка завершена." >> $BACKUPFOLDER/$DATE-result-$PREFIX.txt

# *****************************
# Отправка бэкапа на почту
# *****************************
echo "Отправка почты."
echo "Архив базы данных хранится в файле $BACKUPFOLDER/$DATE-sql-$PREFIX.tar.bz2" >> $BACKUPFOLDER/$DATE-result-$PREFIX.txt
echo "Архив файлов сайта хранится в файле $BACKUPFOLDER/$DATE-files-$PREFIX.tar.bz2" >> $BACKUPFOLDER/$DATE-result-$PREFIX.txt
s-nail -s "$DATE $PREFIX backup" $EMAIL < $BACKUPFOLDER/$DATE-result-$PREFIX.txt

