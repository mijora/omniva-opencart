#!/bin/bash

file=${PWD##*/}.ocmod.zip
[ -f $file ] && rm $file
git archive --prefix ${PWD##*/}/ HEAD -o $file