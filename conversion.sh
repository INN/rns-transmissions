#!/bin/bash

#
# Conversion -- an AP to e-wire and web tool for Religion News Service
#
# Copyright (c) 2011 Religion News Service
# <http://www.religionnews.com>
#
# Version 0.3
# 15 November 2011
#


#
# Make a backup of the original file
#
cp "$1" "$1".bak

#
# Get the input file name without an extension
#
file=${1%.txt}


#
# Put the output file names into variables
#
email_file="$file".EMAIL.txt
web_file="$file".WEB.txt
temp_file="$file".temp.txt


#
# Remove any old versions of the output files
#
rm "$email_file" "$web_file" "$temp_file"


#
# Create output files for the e-wire version, web version, and temporary file
#
touch "$email_file" "$web_file"
cp $1 "$temp_file"


#
# Create the e-wire file
#

# Get the slug from the text
slug=`grep RNS- $1 | sed 's/slug: ^BC-//' | sed 's/<//' | sed -e 's///'`

# Put the boilerplate into variables
dashes="----------------------------------------"
date=`date "+%A, %B %e, %Y" | sed 's/  / /'`
opening="RNS E-WIRE is transmitted as the stories are edited. Please refer to the RNS Opening Budget for information on today's planned stories."
closing="== 30 ==\n\nCopyright `date "+%Y"` Religion News Service. All rights reserved. No part of this transmission may be distributed or reproduced without written permission."

# Put the opening boilerplate into the beginning of the e-wire file
echo -e "$dashes
$date
$dashes
$opening
$dashes
$slug
$dashes \n" >> "$email_file"

# * Perform sed operations on the temp file
# * Send into tr to strip null characters
# * Send into sed to strip line endings
# * Send result into the e-wire file
sed '
  1,6d
  s/\^//
  s/<//
  /Categories:/d
  s/``/"/g
  s/'\'\'\''/'\''"/g
  s/'\'\''/"/g
  s/`/'\''/g
  s/ _ / -- /g
  / END /d
  ' < "$temp_file" | tr -d '\000' | sed 's///g' >> "$email_file"

# Put the closing boilerplate into the end of the e-wire file
echo -e "$closing" >> "$email_file"


#
# Create the web file
#
#
# * Perform sed operations on the temp file
# * Send into tr to strip null characters
# * Send into sed to strip line endings
# * Send result into the web file
#
sed '
  1,6d
  s/\^//
  s/<//
  /Categories:/d
  s/``/"/g
  s/'\'\'\''/'\''"/g
  s/'\'\''/"/g
  s/`/'\''/g
  s/ _ / -- /g
  s/'\'\''/"/g
  / END /d
  s/^    //
  s/^   //
  G
  ' < "$temp_file" | tr -d '\000' | sed 's///g' > "$web_file"

# Cleanup temp file
rm -f "$temp_file"

