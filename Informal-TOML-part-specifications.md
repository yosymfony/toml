
TOML is Obvious, More or Less
====

TOML may be Tom's obvious markup language, but there are some features of implementing readers and writers for TOML configuration files, that are less obvious, at least in the 0.4 Toml Specification, here at https://github.com/toml-lang/toml/blob/master/README.md. To give this version and obvious abbreviation, lets call it TOM04. From an implementer perspective, which I've now began to experience, there are some subtle details coming to light. 

Because its so obvious, I will be repeating stuff that is well described and even assumed by the TOM04 documents.

TOM04 has some good working implementations
-----

Quite a few implementations already exist in a number of programming languages, so its possible to easily construct a make-do simple implementation that caters a fair range of particular text-configurations, and this has been done for earlier version specifications as well. For instance the Rust programming language and its "cargo" tool uses TOML configuration files for project management. I'm sure they have done a thorough job. Many other languages are said to have 'compliant' implementations, as listed on the official wiki page at https://github.com/toml-lang/toml/wiki.

TOM04 needs more test examples that are useful for software conformance testing
-----
In so many ways, the sets of "valid" and "invalid" test examples to run through TOML parsers, and their documented output structures, in whatever language, are the real TOM04 specification, as is for XML and other data formatting specifications. Having reference test documents and reference implementations that are able to read them do strongly indicate that the specification is reasonably self-consistant.  The actual TOM04 github site hasn't got that many full document examples. Where they exist, as in the tricky AOT - table example below, there isn't even a comment to say 'valid' or 'invalid'.
Their is some collective wisdom amoung the many bits of TOML descriptions and example fragments in sepecification, which is a README.md document, even as they set up these collective puzzles.


Array of Tables with [[All.Of.Me.Is.AOT]] seems very under specified.
-----

TOM04 distinguishes between "arrays" and "tables",  which are typical sorts of general use containers for values, typically used as internal data structures in computer programs. Tables aren't multi-column things, they always only 2  column things, the first being a key, and the second being the value. Key and value are seperated by the equals sign, which acts as the "column" divider. In TOML, the newline seperates the table rows.

TOML documents are read in the top to bottom, right to left on the page fashion. In the text document in the computer file, this is a just sequence of bytes, and 1 or 2 bytes conventionally encode the end of each line.
File text encodings are most likely to be ASCII text, or UTF-8, regardless of which byte(s) signify end of line.

Decoding, and normalizing the end of line markers, is usually a pre-parse step, although it can be done with streamming on the fly.  Proper use of Byte-Order-Mark conventions at the beginning of a file are essential for none-ASCII and none-UTF-8 data files conversions.

The innovation of TOM04 is to use the section headers of INI format as a definitive key path, to specify the location in a structure or document, for its subsequent key-values.


AOT technical intentions vs human intentions
------
Here is a tricky case from the TOML examples
```toml
[[fruit.blah]]
  name = "apple"

  [fruit.blah.physical]
    color = "red"
    shape = "round"

[[fruit.blah]]
  name = "banana"

  [fruit.blah.physical]
    color = "yellow"
    shape = "bent"
```
Its even more tricky because there are no comments directly written into the text document with this example, to say how it should be handled, so I have to read the specification, and look for similar cases, on the same site. It is kind of unfair, for a site that supposedly sets the standard. I am probably missing out on all the discussion back-channels.

The naive parsing of tables, just ignores what ever has gone before, and says here is a table, key-path "fruit.bar.physical".  Where do I want it to go? In key path 'fruit.blah.physical', exactly that, from the root.

So what examples are on the TOML spec that tell us about a shared key path of a table with most recent preceding AOT?

After all, [table] and key-value pairs share the same key namespace, and the AOT before it, has just established a "key name space" of 'fruit.0.blash.0', and all subsequent key=value pairs are said to be in that name space.

But table names are fully specified. If I want a table called 'physical' inside the current 'blah' leaf, I cannot do it by specifying 'physical', because that is an entirely new unshared, singular subroot. Here is what I think a test case should produce, from the above toml document. Its in my first comment from my fork, in the branch called 'nearyou', as the only change.  Of course its in my 'mrynn' branch commits which need both the parse-utils and toml repository forks, there are dependent changes in both.  

In more recent versions of php '[' is the same as 'array(' and ')' is the same as the functions closing bracket. They are identical, they produce the same 'byte code'.

```php
'fruit' => [
    [
        'blah' => [
            [
                'name' => 'apple',
                'physical' => [
                    'color' => "red",
                    'shape' => "round",
                ],
            ],
            [
                'name' => 'banana',
                'physical' => [
                    'color' => "yellow",
                    'shape' => "bent",
                ],
            ]
        ]
    ],
]
```

The PHP '[-]' notation is equivalent and compatible with the older notation, in which 'array' is the name of a function, and its argument is text to be interpreted according to what has been interpreted already.  

```php
$mybigarray = array(
'fruit' => [
]
);
```

TOML is the same. Its a series of function calls which interpret the text and build up internal state.
The specification page gives an example to suggest what is the TOML interpretation , by giving us the equivalent expected JSON structure.

"You can create nested arrays of tables as well. Just use the same double bracket syntax on sub-tables. Each double-bracketed sub-table will belong to the most recently defined table element above it."

Here is their cut-and-pasted tom example. The example (not the words) also shows that single bracketed sub-table belongs to the most recently defined table element of the AOT (ie the 0-indexed first table in 'fruit' AOT)

```toml
[[fruit]]
  name = "apple"

  [fruit.physical]
    color = "red"
    shape = "round"

  [[fruit.variety]]
    name = "red delicious"

  [[fruit.variety]]
    name = "granny smith"

[[fruit]]
  name = "banana"

  [[fruit.variety]]
    name = "plantain"
```
Here spec JSON structure interpretation from the official TOML spec. [fruit.physical] goes into the zero'th table member of the AOT defined by [[fruit]], as does the AOT defined by [[fruit.variety]].

This behaviour can't be implemented with naive, AOT context free, table creation. Here it is.

```json
{
  "fruit": [
    {
      "name": "apple",
      "physical": {
        "color": "red",
        "shape": "round"
      },
      "variety": [
        { "name": "red delicious" },
        { "name": "granny smith" }
      ]
    },
    {
      "name": "banana",
      "variety": [
        { "name": "plantain" }
      ]
    }
  ]
}
```

My first naive way around this, was to take the term 'most recently defined' table element literally, (defined by key-paths without the implicit numeric index of tables) and think in terms of remembering most recent contexts. I then made a stacked object to remember the most recent nesting case, which negotiated the above tricksy case. 

But then I thought, what if I imposed a completedly different keypath table, with different root name, in-between any of these 'fruit' example sections. Does this change the relationships? It would most likely wipe out my temporary stacked items. But it by no means changes the relationships and order of the 'fruit' specific data. Therefore, to implement this, I have to concentrate on the key path relationships, between AOT and other AOT and tables, and the current AOT table instances that are implied, and maintain enough information about each AOT.  I don't need table instance information to be stored in quite the same way. 

So the quoted sentence from the TOML site has to be read very carefully, and distrusted as ambiguous and misleading on the superficial level, and the examples information is. Isn't it obvious?

I will try here to say what I think the TOML spec should publish and make clear, or tell me I'm wrong.

Table context depends on the order of shared path relatioships.  If the table came first, before the AOT, it is obviously a different structure altogether, as the table sets up a simple, naive table key path.

Have some not quite so simple alternatives been suggested for table path notation?
------
 AOT establish a key path, that to the human reader, and config file writer, is overtly, without the numeric indexes. A double brackets means the virtual path formed by the latest AOT member definitions. Its always going to be that [[AOTRoot.NextLevel]] actually means [AOTRoot[].NextLevel[]] Here I use the "[]" to mean that the key points to an array of tables. The default for a normal table [AOTRoot.NextLevel]  means [AOTRoot{}.NextLevel{}]
 
The JSON representation 
```JSON
{
"AOTRoot" : [
	{
	"NextLevel" : [
		{}, {}, 
	]
	}, 
	{}, {}
	]
```

What TOML seems out is a way of specifying this JSON representation, where array of tables appear as children of ordinary tables.

```JSON
{
"AOTRoot" : 
	{
		"NextLevel" : [
		{}, {}, 
	]
	}
```
Here the value of key 'AOTRoot' is a singular table, not an Array of Tables. The array brackets, and addition empty tables have been removed. How might we declare this in TOML, while remaining compatible with TOM04?

If it has not already been suggested or formally proposed, the following table declaration syntax rules would be compatible with current AOT specification. 

Array of Tables declaration can be a single brackets enclosed path, using [AOTName[].NextLevel] 
A simple table with an array of tables key value is then [RootTable.AOTTable[]], or more explicitly [RootTable{}.AOTTable[]].

Best, most general table path syntax?
--------------
Another syntax possible is to build on the nested brackets idea. It is possible enclose kust the array of table levels in brackets, as in the path description [RootTable.[NextLevel].JustATable], or [RootTable.JustATable.[ArrayOfTables]] and even [RootTable.[MiddleAOT.EndAOT].LeafTable].  This is a more general formulation of the current all encompassing  [[Everything.Inside.Here.Is.AOTable]] idea.

To me this seems simple and obvious enough. TOML does aim for general JSON structure representation without all the rigidity and fuss.

Because the above example on the TOML site is very specific and clear, I now distrust all those so called compliant software packages for the various programming languages and libraries, since maybe they may don't implement this correctly. Even though I haven't tried any of them yet, other than PHP. (Other than Yosymphony, there was another PHP candidate that I was using initially, and it was working pretty well, and it claimed compliance, until I started to notice it wasn't exactly tested with rigor, and your version appears to have been built with a consistent discipline of design and test together, and used the phpunit framework. Still I did try a few ideas using the other PHP that claimed 0.4.0 support (https://github.com/leonelquinteros/php-toml), and it is pretty good compliance.

Its possible that 0.4.0 doesn't actually do AOT contexts, and this is a new feature that has been added later, that will be further specified in 1.0.  The TOM04 readme is insufficient on this.

So the new TOML seems to be begging to come up with this sort of rule : -

A [table] or AOT that shares any part of a previously declared AOT path, and is an implicit nested child, by the main key components, is nested in the context of that shared path using the current last table members of the shared parts of the AOT path.

The problem now, is my more specific definition of AOT in a table path, could make the above [[fruit]] example illegal. We need an extra brackets around the 'fruit' in [fruit.physical] to make it good, as done below.
```toml
[[fruit]]
  name = "apple"

  [[fruit].physical]
    color = "red"
    shape = "round"

  [[fruit.variety]]
    name = "red delicious"

  [[fruit.variety]]
    name = "granny smith"

[[fruit]]
  name = "banana"

  [[fruit.variety]]
    name = "plantain"
```

Or a rule could be made, that if [[fruit]] has declared that fruit is a direct key to an AOT, any further use of fruit in that key path also implies the same, wether or not the extra brackets get put arround the 'fruit' work. The TOM04 example seems to imply just this idea.

To be a complete specification, the table path [[fruit].[variety]] is equivalent to [[fruit.variety]].
and to be really specific or annoying we could put braces around table path parts,  such as [[fruit].{physical}], or say that no brackets implies curly braces.


