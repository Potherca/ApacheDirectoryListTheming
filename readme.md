## Introduction
Using basic PHP, HTML and CSS and the Apache Module [mod_autoindex](http://httpd.apache.org/docs/2.2/mod/mod_autoindex.html),
we can create a nicer looking directory listing. 

To enable this the following steps need to be taken:

1. A symlink needs to be create to the `directory_list_theming.conf` file from the 
   Apache config directory
2. A symlink needs to be create to the `Directory_Listing_Theme` directory from 
  the directory we want to add the functionality to. 
3. In the Apache config file the directory belongs to we need to include the 
   `directory_list_theming.conf` file.
4. The server needs to be restarted for these changes to take effect.


## Example
Say, for example, we would like to add nicer looking directory listing to the 
user directory of John on our Ubuntu machine. 

### Step 1
First we need to create a link to `directory_list_theming.conf` from the Apache 
`sites-available` directory by calling the following command  (using `sudo` if 
we have to):

    $ ln -s /var/www/Directory_Listing_Theme/directory_list_theming.conf /etc/apache2/sites-available/directory_list_theming.conf

### Step 2
Next we need create the symlink to the `Directory_Listing_Theme` directory from 
John's directory:

    $ ln -s /var/www/Directory_Listing_Theme/ /home/john/Directory_Listing_Theme

### Step 3
Then we open the file `/etc/apache/sites-available/users/john.local` and add the following line:

        Include sites-available/directory_list_theming.conf

So now John's Apache Config file would look a bit like this:

    <VirtualHost *:80>
        ServerName john.local
        DocumentRoot /home/john/www/

        Include sites-available/directory_list_theming
    </VirtualHost>

### Step 4
All that's left to do is restart the Apache Server and we're done:

    $ sudo /etc/init.d/apache2 restart

## How it works

In `/etc/apache/sites-available/` there is a file called `directory_list_theming` 
with the content provided below.

If the module mod_autoindex is present and enabled it will place `/Directory_Listing_Theme/header.php`
above and `/Directory_Listing_Theme/footer.php` below the directory list. Both 
files do some checks and add some niceness like readme file inclusion, extension 
filtering and adding a nicer position header.

The `IndexOptions` and various Icon directives improve the overall layout and feel
of the directory list, adding custom icons for custom filetypes.

Some CSS is added for looks and we're done.

To make sure we don't need each user to have his/her own theme directory we use
a symlink, linking to the version currently checked out of the repository. Also,
instead of having to add all those directives to each user, we simply include a 
config file for a given directory.

### Content of `directory_list_theming.conf`

    # DIRECTORY CUSTOMIZATION
    # http://httpd.apache.org/docs/2.0/mod/mod_autoindex.html
    #    IndexOrderDefault Ascending Name
    Options +Indexes
    <IfModule mod_autoindex.c>
     
        HeaderName /Directory_Listing_Theme/header.php
        ReadmeName /Directory_Listing_Theme/footer.php

        IndexIgnore readme.* readme-footer.* Directory_Listing_Theme

        IndexOptions FancyIndexing
        IndexOptions FoldersFirst IgnoreCase XHTML NameWidth=* DescriptionWidth=*
        IndexOptions SuppressHTMLPreamble SuppressRules HTMLTable
        IndexOptions IconHeight=16 IconWidth=16


    #    IndexOptions VersionSort
        IndexOptions ScanHTMLTitles

        AddDescription "PHP File" *.php *.PHP

    ################################################################################
    # @TODO: Replace AddIcon by AddIconByType
    # use http://www.google.com/search?q=list%20of%20mime%20types
     AddIconByType (IMG,/Directory_Listing_Theme/img/picture.png) image/*
    ################################################################################

    DefaultIcon /Directory_Listing_Theme/img/page_white.png

        AddIcon /Directory_Listing_Theme/img/page_white_back.png ..
        AddIcon /Directory_Listing_Theme/img/folder.png ^^DIRECTORY^^
        AddIcon /Directory_Listing_Theme/img/page_white_bmp.png .bmp .BMP
        AddIcon /Directory_Listing_Theme/img/page_white_bak.png .bak .BAK
        AddIcon /Directory_Listing_Theme/img/css.png .css .CSS
        AddIcon /Directory_Listing_Theme/img/page_white_cur.png .cur .CUR
        AddIcon /Directory_Listing_Theme/img/page_white_db.png .db .DB
        AddIcon /Directory_Listing_Theme/img/page_white_word.png .doc .DOC
        AddIcon /Directory_Listing_Theme/img/page_white_ess.png .ess .ESS
        AddIcon /Directory_Listing_Theme/img/page_white_fla.png .fla .FLA
        AddIcon /Directory_Listing_Theme/img/page_white_h3m.png .h3m .H3M
        AddIcon /Directory_Listing_Theme/img/page_world.png .htm .HTM .html .HTML
        AddIcon /Directory_Listing_Theme/img/script.png .js .JS
        AddIcon /Directory_Listing_Theme/img/page_white_log.png .log .LOG
        AddIcon /Directory_Listing_Theme/img/page_white_mp3.png .mp3 .MP3 .mp4 .MP4 .mid .MID .m3u .M3U .pls .PLS .wav .WAV
        AddIcon /Directory_Listing_Theme/img/page_white_msi.png .msi .MSI
        AddIcon /Directory_Listing_Theme/img/page_white_acrobat.png .pdf .PDF
        AddIcon /Directory_Listing_Theme/img/page_white_psd.png .psd .PSD
        AddIcon /Directory_Listing_Theme/img/page_white_php.png .php .PHP .phtml .PHTML .php3 .PHP3
        AddIcon /Directory_Listing_Theme/img/page_white_compressed.png .rar .RAR .zip .ZIP .gz .GZ
        AddIcon /Directory_Listing_Theme/img/page_white_flash.png .swf .SWF
        AddIcon /Directory_Listing_Theme/img/page_white_text.png .txt .TXT
        AddIcon /Directory_Listing_Theme/img/page_white_xpi.png .xpi .XPI
        AddIcon /Directory_Listing_Theme/img/page_white_xml.png .xml .XML
        AddIcon /Directory_Listing_Theme/img/page_white_excel.png .xls .XLS
        AddIcon /Directory_Listing_Theme/img/page_white_wmv.png .wmv .WMV

    </IfModule>
