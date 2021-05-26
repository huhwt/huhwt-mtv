ℍ&ℍwt - HuH Extensions for webtrees - Multi-Treeview
============================

[![Latest Release](https://img.shields.io/github/v/release/huhwt/huhwt-mtv)][1]
[![webtrees major version](https://img.shields.io/badge/webtrees-v2.x-green)][2]
[![Downloads](https://img.shields.io/github/downloads/huhwt/huhwt-mtv/v1.0/total)]()

Extensions for webtrees to check and display duplicate Individuals in the database.

Introduction
-----------

If you have worked for a few years in one of the big genealogy services, chances are that you will get duplicates by using matching services, because the quality of the data is sometimes quite questionable.

Then it is handy to have a function to check the data with visual aids not only for each match separately, but on a screen at the same time.

The matching list in the 'Find duplicates'-view for 'Individuals' is expanded by a 'Interactive Check' entry, showing 'Interactive tree' for each individual on the screen together. This also happens when more than 2 individuals are captured by the function.

Installation and upgrading
--------------------------
... in the usual way: Download the zip file, unzip it into the modules_v4 directory, and that's it. You should remove the existing version completely before.

Thanks
--------------------------

Special thanks to Hermann Harthentaler for the test, suggestions and criticism.

Thanks to TheDutchJewel for the translation into Dutch.

Development
-------------------------

[TODO]

By now there are false-positives because of matching happens even when individuals are sharing the '_MARNM'-tag only. The filtering function should be sharpened by explicitly requesting for 'NAME'-tags.

Hard-core workaround: In AdminService.php insert after line 136:

    >            ->where('n_type', '=', 'NAME')          /** EW.H - MOD ... avoid false positives because of tag '_MARNM' */


Bugs and feature requests
-------------------------
If you experience any bugs or have a feature request for this theme you can [create a new issue][3].

[1]: https://github.com/huhwt/huhwt-mtv/releases/latest
[2]: https://webtrees.net/download
[3]: https://github.com/huhwt/huhwt-mtv/issues?state=open