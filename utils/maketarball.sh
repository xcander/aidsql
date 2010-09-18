#!/bin/sh

#Not optimal but whatever, it's just for saving the job of making a tarball
#not containing .svn directories 

rm -rf /tmp/aidsql-temp
mkdir /tmp/aidsql-temp
export DATE=$(date +%m%d%Y);
cp -r .svn *.txt class launcher.php config interface utils /tmp/aidsql-temp
echo "Deleting .svn files ...";
find ./ -name ".svn"| xargs rm -rf 
echo "Packing aidsql-$DATE.tgz ...";
tar -czf "aidsql-$DATE.tgz" utils *.txt launcher.php config interface class
rm -rf *.txt class launcher.php config interface utils
echo "Restoring files ...";
mv /tmp/aidsql-temp/* .
mv /tmp/aidsql-temp/.svn .
rm -rf /tmp/aidsql-temp
echo "Done"
