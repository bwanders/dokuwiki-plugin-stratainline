Strata Inline
=============

__This dokuwiki plugin is no longer maintained. Due to time constraints and no longer using this plugin myself, I can no longer properly maintain it.__

This plugin depends on [bwanders/dokuwiki-strata](https://github.com/bwanders/dokuwiki-strata).

Strata Inline is an inline data entry and query plugin for dokuwiki.


Page references
---------------

In normal dokuwiki pages you link to another page with `[[Other page]]`. For
humans the text makes it clear what the relation between the two pages is. If
the wiki knows the type of relation as well you could have automatic indices,
nice lists of pages related to the one you're looking at, or simple tables
showing the relation between these pages.

With this plugin you an use the normal dokuwiki syntax, and prefix a relation
type with `~`:

```
====== Famous Chef ======

Famous Chef is well-known for their delicious [[Recipe~Carrot Pie]].
```
Now, you can list all recipes and related chefs with the strata
query `?chef Recipe: ?course`.


Inline lists
------------

Strata allows you to output lists, but those always break up the flow of the
text. The inline list works inside a paragraph, embedding the results as if
they are text:
```
After visiting Famous Chef's restaurant a few months back, which is well-known
for his {{list>?r | [[Famous Chef]] Recipe: ?r }}, we took a walk on the beach.
```


Inline data
-----------

Strata allows you to use data blocks. But sometimes you do not want to repeat
yourself, and the text is already there:
```
Famous Chef was born on [(Birthday[date]: 1973-01-01)]
```
Which will do the same as:
```
<data>
Birthday[date]: 1973-01-01
</data>
```
But will simply display as inline text.


Formatting only
---------------

Every once in a while, it would be nice to use a strata type just because it
displays the value in a way that looks good: `{([date]:1973-01-01)}`.



