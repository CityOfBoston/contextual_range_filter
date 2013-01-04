
CONTEXTUAL RANGE FILTER
=======================

This is a simple plugin for Views that adds the option to contextually filter
a view not just by a single value, but also by RANGE.

Both numeric (float, integer) and alphabetical (string) ranges are supported, as
well as lists.

Taxonomy term ids, node ids etc are special cases of integers so will work also.

You may use the OR ('+') and the negate operators. You negate by ticking the
"Exclude" box on the Views Contextual filter configuration panel, in the "More"
section.

Just like normal contextual filters, contextual range filters may be set by
appending filter values to the URL. Alternatively, if you want to use a UI,
install the "Views Global Filter" module, http://drupal.org/project/global_filter.
This then enables a variety of widgets for you to enter your filter ranges,
text fields as well as range sliders. For the latter checkout the "Slide with
Style" module, http://drupal.org/project/select_with_style.
Views Global Filter provides the added benefit of it remembering your latest
contextual filter selections across multiple views for the duration of your
session, and even after logout.

To create a contextual range filter, first create a plain contextual filter as
per normal. I.e. in the Views UI open the "Advanced" field set (upper right) and
click "add" next to the heading "Contextual filters". On the next panel select
the field or property that needs to be contextually filtered and "Apply". If you
want to take advantage of Views Global Filter, then the next panel is where you
press the "Provide default value" radio button to select the appropriate Global
Filter option. If you don't use a global filter you may pick any of the other
options. Fill out the remainder of the configuration panel, press "Apply" and
"Save" the view.

Now visit the Contextual Range Filter configuration page,
admin/config/content/contextual_range_filter, find your contextual filter name
and tick the box next to it, to turn it into a contextual range filter. Press
"Save configuration".

If you don't use the Views Global Filter widgets, then you set your contextual
filters by appending "arguments" to the view's URL. Using the double-hyphen '--'
as a range separator, you can filter your view output like so:

  http://yoursite.com/yourview/100--199.99  (numeric range)
  http://yoursite.com/yourotherview/k--qzz  (alphabetical range)
  http://yoursite.com/somebodysview/1--9    (list range, using list keys)
  http://yoursite.com/somebodysview/infant--retiree (list range, using list keys)

Ranges are inclusive of "from" and "to" values and will be case-insensitive,
unless your database defaults otherwise. In your database's alphabet, numbers
and special characters (@ # $ % etc.) generally come before letters , e.g.
"42nd Street" comes before "Fifth Avenue" and also before "5th Avenue". The
first printable ASCII character is the space (%20). The last printable ASCII
character is '~'. So to make sure everything from "!Hello" and "@the Races" up
to and including anything starting with the letter r is returned use ' --r~'.

You may omit the start or end values to specify open-ended filter ranges:

  http://yoursite.com/yourview/100--

Multiple contextual filters (eg Title followed by Price) are fine and if you
ticked "Allow multiple ranges" also, you can use the plus sign to OR filter
ranges like this:

  http://yoursite.com/yourthirdview/a--e~+k--r~/--500

Or, if "Glossary mode" is on, so that only the first letter matters, the above
becomes:

  http://yoursite.com/yourthirdview/a--e+k--r/--500

You may use a colon ':' instead of the double hyphen.
Use either '--', ':' or 'all' to return all View results for the associated
filter:

  http://yoursite.com/yourthirdview/all/-100--999.99

ASCII AND UTF CHARACTER ORDERING
o http://en.wikipedia.org/wiki/UTF-8