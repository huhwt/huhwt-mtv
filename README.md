ℍ&ℍwt - HuH Extensions for webtrees - Multi-Treeview
============================

[![Latest Release](https://img.shields.io/github/v/release/huhwt/huhwt-mtv)][1]
[![webtrees major version](https://img.shields.io/badge/webtrees-v2.1-green)][2]
[![Downloads](https://img.shields.io/github/downloads/huhwt/huhwt-mtv/total)]()

Extensions for webtrees to check and display duplicate Individuals in the database.

Attention:
~~~
  This module requires to be operated in a PHP 8.2-onward system 
  environment due to some features not have been available before.

  Systems with PHP 8.1 environment have to use Release 2.1.17.x.
~~~

Introduction
------------

If you have worked for a few years in one of the big genealogy services, chances are that you will get duplicates by using matching services, because the quality of the data is sometimes quite questionable.

Then it is handy to have a function to check the data with visual aids not only for each match separately, but on a screen at the same time.

The matching list in the 'Find duplicates'-view for 'Individuals' is expanded by a 'Interactive Check' entry, showing 'Interactive tree' for each individual on the screen together. This also happens when more than 2 individuals are captured by the function.

Description of the procedure
----------------------------

Duplicates are identified by matching events to people. When importing the GEDcom into Webtrees, the data content is distributed to various tables. Events are identified in GEDcom by special TAGs, each of which is assigned an event date. In web trees, this content is stored in the wt_dates table, the date is given the TAG identifier of the event as d_fact. People have names, these are stored in webtrees in the wt_name table. Both tables also contain the XREFs for each entry, so that it can be differentiated which person ID the entry should be assigned to. The comparison is made by analyzing which events with the same date have taken place with which people with the same name, these are then output as potential duplicates.

And this is where it gets difficult. Any event with a date is listed in wt_dates. And as an entry in wt_name, not only the official NAME-TAG is adopted, but also TAGs such as _MARNM or _AKA, which do not correspond to the standard but are declared as legacy or individually. The contents of these TAGs are not as significant as the contents of the NAME TAG - it contains the complete personal name with all its components. Thus, if any arbitrary events are associated with undifferentiated names, there is a higher probability that the result will be inconsistent - garbage in, garbage out.

I had achieved a certain sharpening of the result with an additional clause in the basic query in the module 'app/Services/AdminService.php'. By restricting the query to entries that only contain the NAME TAG, there were fewer duplicate messages. A further reduction resulted if only relevant events were filtered, that would be BIRT, CHR, BAPM, DEAT, BURI as the contents of the d_fact field in wt_dates.

So that you don't have to intervene in the code of the Webtrees core, these options are now separated out as a separate function, so they are no longer necessarily overwritten by a Webtrees update. You activate it via "Settings" in the administration-all modules-overview. If they are inactive, the original Webtrees function continues to run, if one or both are active, the query is executed accordingly.

##### A notice:
##### If the extension module ['huhwt-cce'](https://github.com/huhwt/huhwt-cce) is also installed, you can add the displayed people to the ClippingsCart.


Installation and upgrading
--------------------------
... in the usual way: Download the zip file, unzip it into the modules_v4 directory, and that's it. You should remove the existing version completely before.

Thanks
--------------------------

Special thanks to Hermann Harthentaler for the test, suggestions and criticism.

Thanks to TheDutchJewel for the translation into Dutch.

Thanks to BernatBanyuls for the translations into Catalan und Spanish.

Development
-------------------------

[TODO]

...

Bugs and feature requests
-------------------------
If you experience any bugs or have a feature request for this theme you can [create a new issue][3].

[1]: https://github.com/huhwt/huhwt-mtv/releases/latest
[2]: https://webtrees.net/download
[3]: https://github.com/huhwt/huhwt-mtv/issues?state=open