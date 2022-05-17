# Version History

## Releases
| Date              | Version | Name                                  |
|-------------------|---------|---------------------------------------|
| May 17, 2022      | 0.6.0   | Entries Upgraded                      |
| May 4, 2022       | 0.5.3   | Upgrade to php 8.1.5                  |
| May 3, 2022       | 0.5.2   | Git Revamp                            |
| April 20, 2022    | 0.5.1   | Git Bit!                              |
| April 13, 2022    | 0.5.0.1 | Standard views and Edits              |
| March 30, 2022    | 0.5.0   | Standard Attributes are in the house! |
| March 26, 2022    | 0.4.4   | Working with tag attributes           |
| March 22, 2022    | 0.4.3   | Back into the workflow                |
| March 15, 2022    | 0.4.2   | Searching Things                      |
| October 31, 2021  | 0.4.1   | Entries First                         |
| October 26, 2021  | 0.4.0   | Tags Oh My!                           |
| September 2, 2021 | 0.3.0   | Import Export                         |
| August 9, 2021    | 0.2.0   | Projects and Users Exist              |
| July 4, 2021      | 0.1.0   | First Commit                          |

### May 17, 2022       | 0.6.0   | Entries upgraded
* Able to embed tags into the BB Code, and then do changes on it by tag searches and substitution to show a view of the code
* Entry Nodes db table added
* Have a tag search box to insert the flow tags as bb code, the editor can convert this to html too, or drag and drop html to it
* Applied tags can now point to other tags, does not show up on things
* Some entry concepts like members and children not used, and small code parts removed
* Entry routes tested for updating and deleting
* Entries now have a fuller gui where they can be seen in the list, viewed, edited and deleted
* Refactored some entry handling code
* Added print_nice to the twig functions
* Updated some php libraries
* Added a submit js handler that can be useful later
* Refactored some search and param layout and accessors
* Improved the ajax handler class, now can be used for all controllers incluing entries
* Brought some minor things up to 1.8.1 linter standards
* Some bug fixes in the gui

### May 4, 2022       | 0.5.3   | Upgrade to php 8.1.5
From 7.4 not much changed in functionality

### May 3, 2022       | 0.5.2   | Git Revamp
Makes code work well for copying and updating from different sources, and the git support code is much improved

### April 20, 2022    | 0.5.1   | Git Bit!
More work with standard attibutes, which can also now be project settings

### April 13, 2022    | 0.5.0.1 | Standard views and Edits

Standard attibutes have each their own mini views and editors

### March 30, 2022    | 0.5.0   | Standard Attributes are in the house!

Added a few concept called standard attribues, which are created from the tag attributes

### March 26, 2022    | 0.4.4   | Working with tag attributes

A rebase for the tag attributes

### March 22, 2022    | 0.4.3   | Back into the workflow

After a long pause resumed work, this version touched up earlier work and added a unified way to search and list things

### March 15, 2022    | 0.4.2   | Searching Things

Added entries as new mini projects under each project. The entries will later be most of this code base 'meat and potatoes'

### Entries First  ~ Released October 31, 2021

### Tags Oh My!  ~ Released October 26, 2021

Each project can create and manage a list of tags

A tag has a name , a possible parent tag to inherit from, and a guid (and some other things like timestamps)

Each tag can have 0 or more attributes. An attribute can have a name, an integer value and a text value.
An attribute can also link to any one user or entry or project.
A tag will inherit all the attributes of its ancestors, with the ability to overwrite exiting tags for itself and its own descendants.
Some attributes are used to style the tag name or things the tag points too, or when a tag is applied to something.
These are called standard attributes. standard attributes of color, background-color are currently used to change the tag name

Projects, Users and Entries can be assigned tags. This is called applying tags.
Tags are assigned by any project but if a project is visible the tag names and properties are too

### Import Export ~ Released September 2,2021

Save projects to an internal repository, see the commits on a web page, auto push to remote repo for each save

Can import a new branch , or commit of the same repo (as long as derived from current branch )
