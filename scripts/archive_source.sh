#!/bin/sh
#

script_dir=`dirname $0`
project_dir=`cd $script_dir/..; basename $PWD`
pushd $script_dir/../..
tar zcvf bronto_treqs.tgz "$project_dir" --exclude=treqs.conf
popd
exit $?
