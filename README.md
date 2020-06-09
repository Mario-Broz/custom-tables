# Custom Tables

[![GitHub stars](https://img.shields.io/github/stars/joomlaboat/custom-tables.svg?style=social&label=Star&maxAge=2592000)](https://GitHub.com/joomlaboat/custom-tables/stargazers/)


[![GitHub issues](https://img.shields.io/github/issues/joomlaboat/custom-tables.svg)](https://GitHub.com/joomlaboat/custom-tables/issues/) [![HitCount](http://hits.dwyl.io/custom-tables/badges.svg)](http://hits.dwyl.io/custom-tables/badges)


Custom Tables for Joomla. Allows you to add Tables, Fields and Layouts/Pages to create a catalog, edit form or details page.
It has 40 Field Types like Integer, Decimal, Text String, Date, Time, Email, Color, Image, File, Table Join, User, Language, etc.
Edit Form Input boxes depend on the field type, Date field type will show a calendar, Color - Color Picker, Image - Image Uploader, etc.
Tables can be connected using Table Join field type and Multiple record table joins.
Layout Editor with Twig like language has an Auto-Create button that will create a new layout based on the list of fields the table has.
Tables are stored in MySQL or Maria database, all queries and field values are sanitized on submit and before saving.

When creating a new Custom Table, it is important to get the schema right the first time, though this won't be difficult to change later.
The schema is like the blueprint for the table. You need to define each field (column) along with its parameters.
