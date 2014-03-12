README
======

"If your photographs aren't good enough, you're not close enough." — Robert Capa.


Author
------

* "Anton Bondar" <anton@zebooka.com> — http://zebooka.com


Installation
------------

Run `make` to build phar package.
Add `build/photos-downloader.phar` to directory within your executable path.
For example, add it to `/usr/local/bin/` directory.
Set executable flag — `chmod +x photos-downloader.phar`.


Requirements
------------

You need `composer` to build photos downloader tool.
You need to install `exiftool` as well. This program is used to read exif from photos.


Usage
-----

`photos-downloader.phar -t PATH [options] FROM_PATH [ FROM_PATH ... ]`

Run `photos-downloader.phar -h` to get full options list.


License
-------

Standard MIT License — http://opensource.org/licenses/MIT

http://zebooka.com/soft/LICENSE/

Copyright (c) 2013, Anton Bondar.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.


Algorythm (to be verified and modified; uncomplete)
---------

1. Search for photos recursively (if -R options specified — non-recursively) in specified directories.
2. Split photo file name into tokens by underscore "_" symbol.
3. Check if first token is one letter string. If so — remember it as prefix and remove from tokens.
4. Drop all empty tokens.
5. Search for camera name in tokens. If multiple cameras found in tokens, then issue a warning and use first one. Remove all found cameras from tokes.
6. Read exif.
7. If camera was not found from tokens, then detect camera by exif.
8. Get author from tokens.
9. If -a author is specified and author found from tokens are not the same, then skip that photo. If -a author is specified and there is no author found from tokens, and detected camera does not belong to that author, then skip that photo. If -a author is specified and there is no author found from tokens, and detected camera does belong to that author or camera not detected, then continue processing that photo. If -A author is specified, then set author as specified and process photo anyway.
13. If camera is not film (digital or not detected), then get date and time of photo from exif. If camera is film or date/time of photo not found in exif, then search for it in tokens. Next token after date (YYYYMMDD) is "time,shot" token (number,number). If camera is digital and date/time get from exif, then search for "time,shot" token (HHMMSS,number). If time is taken form token, then it is not forced to be in HHMMSS format — it is actually just some number for uniqueness. The next number after comma (in "time,shot" token) is considered as shot number and can be increased, if there is another photo with same name.
14. Get all known tokens (raw converters and post processings).
15. If photo file name is ABCD1234a and one of parent directories in DCIM, then drop all other tokens. Also drop other tokens if -X option specified.
16. Specify upload directory. If -D option specified, then use -t PATH option as upload directory, if not — append path calculated by following algorythm. If date is fully known and camera is unknown or digital, then upload directory is YYYY/MM_month/DD/. If date not fully known or film camera, then upload directory is YYYY/. Fully known date means that all symbols in YYYYMMDD are digital, while not fully known date means that some of them can be Y, or M, or D.
17. Combine new photo filename as "prefix_date_time,shot_author_knownTokens_otherTokens.extension". 
18. Check if there are photos with such basename in upload directory. If there are, then increase shot number and repeat check.
19. Move all photos with same basename and different extensions to upload diretory with combined filename.
20. Continue with next photo.
