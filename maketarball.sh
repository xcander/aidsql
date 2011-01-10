#!/bin/bash
export _DT=$(/bin/date +%m%d%Y);
mkdir /tmp/aidsql-temp &>/dev/null
cp -r * /tmp/aidsql-temp
cp -r .svn /tmp/aidsql-temp
find /tmp/aidsql-temp/ -name ".s*"|xargs rm -rf &>/dev/null
find /tmp/aidsql-temp/ -name ".*.*"|xargs rm -rf &>/dev/null
find /tmp/aidsql-temp/ -name ".*.*"|xargs rm -rf &>/dev/null
cd /tmp/aidsql-temp
tar -czf aidsql-$_DT.tgz *
mv aidsql-$_DT.tgz $HOME;
echo "DONE aidsql-$_DT.tgz";
rm -rf /tmp/aidsql-temp
