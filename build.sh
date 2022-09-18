#!/bin/bash

SCRIPT=$(readlink -f $0)
SCRIPT_PATH=`dirname $SCRIPT`
BASE_PATH=`dirname $SCRIPT_PATH`

RETVAL=0
VERSION=0.4
SUBVERSION=3
IMAGE="bayrell/letsencrypt"
TAG=`date '+%Y%m%d_%H%M%S'`

case "$1" in
	
	test)
		echo "Build $IMAGE:$VERSION.$SUBVERSION-$TAG"
		docker build ./ -t $IMAGE:$VERSION.$SUBVERSION-$TAG --file Dockerfile
		docker tag $IMAGE:$VERSION.$SUBVERSION-$TAG $IMAGE:$VERSION.$SUBVERSION
		docker tag $IMAGE:$VERSION.$SUBVERSION-$TAG $IMAGE:$VERSION
	;;
	
	amd64)
		echo "Build $IMAGE:$VERSION.$SUBVERSION-amd64"
		docker build ./ -t $IMAGE:$VERSION.$SUBVERSION-amd64 \
			--file Dockerfile --build-arg ARCH=amd64
	;;
	
	arm64v8)
		echo "Build $IMAGE:$VERSION.$SUBVERSION-arm64v8"
		docker build ./ -t $IMAGE:$VERSION.$SUBVERSION-arm64v8 \
			--file Dockerfile --build-arg ARCH=arm64v8
	;;
	
	manifest)
		rm -rf ~/.docker/manifests/docker.io_virtual_space-*
		
		docker tag $IMAGE:$VERSION.$SUBVERSION-amd64 $IMAGE:$VERSION-amd64
		docker tag $IMAGE:$VERSION.$SUBVERSION-arm64v8 $IMAGE:$VERSION-arm64v8
		
		docker push $IMAGE:$VERSION.$SUBVERSION-amd64
		docker push $IMAGE:$VERSION.$SUBVERSION-arm64v8
		docker push $IMAGE:$VERSION-amd64
		docker push $IMAGE:$VERSION-arm64v8
		
		docker manifest create --amend $IMAGE:$VERSION.$SUBVERSION \
			$IMAGE:$VERSION.$SUBVERSION-amd64 \
			$IMAGE:$VERSION.$SUBVERSION-arm64v8
		docker manifest push --purge $IMAGE:$VERSION.$SUBVERSION
		
		docker manifest create --amend $IMAGE:$VERSION \
			$IMAGE:$VERSION-amd64 \
			$IMAGE:$VERSION-arm64v8
		docker manifest push --purge $IMAGE:$VERSION
	;;
	
	all)
		$0 amd64
		$0 arm64v8
		$0 manifest
	;;
	
	upload-image)
		
		if [ -z "$2" ] || [ -z "$3" ]; then
			echo "Type:"
			echo "$0 upload-image $VERSION.$SUBVERSION raspa 172"
			echo "  $VERSION.$SUBVERSION - version"
			echo "  raspa - ssh host"
			echo "  172 - bandwidth KiB/s"
			exit 1
		fi
		
		version=$2
		ssh_host=$3
		bwlimit=172
		
		if [ ! -z "$4" ]; then
			bwlimit=$4
		fi
		
		mkdir -p images
		
		echo "Save image"
		docker image save bayrell/letsencrypt:$version | gzip \
		    > ./images/letsencrypt-$version.tar.gz

		echo "Upload image"
		ssh $ssh_host "mkdir -p ~/images"
		ssh $ssh_host "yes | rm -f ~/images/letsencrypt-$version.tar.gz"
		
		time rsync -aSsuh --info=progress2 --bwlimit=$bwlimit ./images/letsencrypt-$version.tar.gz \
			$ssh_host:images/letsencrypt-$version.tar.gz

		echo "Load image"
		ssh $ssh_host "docker load -i ~/images/letsencrypt-$version.tar.gz"
	;;
	
	*)
		echo "Build $IMAGE:$VERSION.$SUBVERSION"
		echo "Usage: $0 {amd64|arm64v8|manifest|all|test|upload-image}"
		RETVAL=1

esac

exit $RETVAL