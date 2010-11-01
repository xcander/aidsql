#!/bin/sh

#Not optimal but whatever, it's just for saving the job of making a tarball
#not containing .svn directories 

rm -rf /tmp/aidsql-temp
mkdir /tmp/aidsql-temp
export DATE=$(date +%m%d%Y);
cp -r .svn functions run aidSQL *.txt class launcher.php config interface utils tests /tmp/aidsql-temp
echo "Deleting .svn files ...";
find ./ -name ".svn"| xargs rm -rf 
echo "Packing aidsql-$DATE.tgz ...";
tar -czf "aidsql-$DATE.tgz" *
rm -rf functions run *.txt class launcher.php config interface utils tests
echo "Restoring files ...";
mv /tmp/aidsql-temp/* .
mv /tmp/aidsql-temp/.svn .
rm -rf /tmp/aidsql-temp
echo "Done"
