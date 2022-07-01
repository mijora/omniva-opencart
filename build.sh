#!/bin/bash

file=${PWD##*/}.ocmod.zip
[ -f $file ] && rm $file
git archive HEAD -o $file