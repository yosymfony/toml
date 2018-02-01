
What is so obvious about TOML?
====

TOML may be Tom's obvious markup language, but there are some features of implementing readers and writers for TOML configuration files, that are less obvious, at least in the 0.4 Toml Specification, here at https://github.com/toml-lang/toml/blob/master/README.md. To give this version and obvious abbreviation, lets call it TOM04. From an implementer perspective, which I've now began to experience, there are some subtle details coming to light. 

Because its so obvious, I will be repeating stuff that is well described and even assumed by the TOM04 documents.

TOM04 has some good working implementations
-----

Quite a few implementations already exist in a number of programming languages, so its possible to easily construct a make-do simple implementation that caters a fair range of particular text-configurations, and this has been done for earlier version specifications as well. For instance the Rust programming language and its "cargo" tool uses TOML configuration files for project management. I'm sure they have done a thorough job. Many other languages are said to have 'compliant' implementations, as listed on the official wiki page at https://github.com/toml-lang/toml/wiki.

TOM04 needs more test examples that are useful for software conformance testing
-----
In so many ways, the sets of "valid" and "invalid" test examples to run through TOML parsers, and their documented output structures, in whatever language, are the real TOM04 specification, as is for XML and other data formatting specifications. Having reference test documents and reference implementations that are able to read them do strongly indicate that the specification is reasonably self-consistant.  The actual TOM04 github site hasn't got that many full document examples. Where they exist, as in the tricky AOT - table example below, there isn't even a comment to say 'valid' or 'invalid'.
Perhaps the comments, as many bits of TOML documents and example fragments are in the ReadMe document, which are described clearly, even as they set up some collective puzzles.


Arrays, Tables, Key-values, and Array of Tables.
-----

TOM04 distinguishes between "arrays" and "tables",  which are typical sorts of general use containers for values, typically used as internal data structures in computer programs. Tables aren't multi-column things, they always only 2  column things, the first being a key, and the second being the value. Key and value are seperated by the equals sign, which acts as the "column" divider. In TOML, the newline seperates the table rows.

TOML documents are read in the top to bottom, right to left on the page fashion. In the text document in the computer file, this is a just sequence of bytes, and 1 or 2 bytes conventionally encode the end of each line.
File text encodings are most likely to be ASCII text, or UTF-8, regardless of which byte(s) signify end of line.

 Decoding, and normalizing the end of line markers, is usually a pre-parse step, although it can be done with streamming on the fly.  Proper use of Byte-Order-Mark conventions at the beginning of a file are essential for none-ASCII and none-UTF-8 data files conversions.

 Internal giant hash table equivalent
 ---------

Like XML,YML,JSON , etc, a TOML parser reads the document and constructs an internal data structure. Because a 'DOM' is already well used, and TOML builds a arrays/tables like JSON, we should probably call the internal structure a 'TOM', as in TOML's Obvious Model.

If there is no [table.heading] brackets section names above the key=value pairs, these pairs are in the root of the internal TOM. If there is a such a brackets section, the most recent one encountered sets the key-prefix path, or internal storage location, for key-value pairs.

Since object-oriented progamming became faddish, and memory sizes and many sorts of information databases have been developed, the tables and arrays structures have become more complicated. All these objects in computer programs are more or less easily implemented as hierarchies of arrays and tables. 

Tables are declared as key paths that look like typical INI [section.headers]. The key=value pairs immediately underneath, until the next section header, belong to table located by its key path.
Tables can be values, as "inline tables" are indicated as matching pairs of braces enclosing key-value pairs. Because listing the key=value pairs vertically under a [table.section.header] is so neat, readable, and extendable, the inline tables as values, are limited to exactly one line, which most people don't want to horizontal scroll a long way on the virtual computer screen page to scan.

Avoid key path clashes
----
Adding a key=value underneath a [table.path] declaration, is the same as declaring a [table.path.key]. In most implementations the internal TOM starts at the root as keyed hash table. So a valid TOML can be just a list of key=value pairs, one for each line, without any [table] declarations. Once a key is declared, it can't be used as a table name, at that place in the key path. Once a table called [key.path.table] is declared, a table [key.path], before or after, cannot use a key called "table". In the internal implementation, they have exactly the same key path. Only [key.path.table] species that there is a value of type "table" stored in the key path "path.table".  The implementation is usually a hierarchy of tables, rather than a single table that is indexed by ever longer, compound key-path strings. 

In PHP, a flexible interpreted language widely used for web-site implementations. It has implemented array and table properties in the same container object, called the "array".  Nested PHP array object represented in PHP source code, are close to ideal to represent the internal TOML structure.

The Lua programming language also uses flexible containers with table and array optimized functionality, and corresponding flexible text source code for data description. 

PHP key value pairs are represented as "key" => "value".  PHP strings in source code arrays most likely have a single or double quote, the difference not being essential here. Also commas seperate consecutive source code array items.

TOM04
The next three lines are a valid TOML text format.
```toml
key = 'one value'
"key2" = 'two value'
3 = 34
```
TOML doesn't need to quote keys or values if there are no space characters within the key or values. 

And this is a likely source code representation of the data structure that the TOML parser will create.
```php
$root = [
	'key' => 'one value',
	'key2' => "two value",
	'3' => 34
];
```

The next three lines are a valid TOML text format.
```toml
[table]
key = 'one value'
3 => 34
```
TOML doesn't need to quote keys or values if there are no space characters within the key or values. 

And this is a likely source code representation of the data structure that the TOML parser will create.
```php
$root = [
	'table' => [
		'key' => 'one value',
		'3' => 34
	]
];
```

PHP can use any scalar type - int, bool, float, string - as a key, and a PHP TOML parser needs to turn all non-string keys such as integers into strings. Values can be any type. Hence toml specifies that 3=34 means the string key '3' stores the integer value 34.


Arrays are lists of items with an implicit numeric index.  An implementation can start at any index value, it doesn't matter, as long as same sequence happens. Arrays are considered to lists of kind of items. Each item in an array has the same type. Unless its another array of the same type, representing some internal structure. Arrays are inline, which means they are right hand side of a key = value pair.

TOML confuses everyone by coming up with a simple, obvious way to specify "Array of Tables" - AOT for acronym. These can only be declared  as section header key-paths contained within [[ double.brackets ]].


 So TOM04 finds it wrong to mix up value types, but there can be many nested sub-arrays of varying lengths. Multidimensional, or varying length dimensions, can be realized, as long as the internal structure can represent them, and they are all of the same value type.

Data Terminal Leaf's are Values, and Values can be inline tables or arrays, but with restrictions
-----
Tables contain key-value pairs, which are usually implemented with fast hash table lookup for the key values.
Every data item in a TOM04 document, has a unique key path, consisting of key names, which are always encodable as strings, and implicit indexes, being an ordinal number indicating a list position. Zero is most often used as the first index.

Fortunately, the inline tables and arrays as values have limitations, that allow TOML to avoid unrealistic nesting possibilities.  

A TOM04 parser, processes [[AOT.key.paths]] and [ordinary.table.paths], to determine the current location for the succeeding lines of key = value pairs. Values can also be inline tables or inline arrays, and limitations on these limit their key clash potential.  Inline arrays have order and dimensions,  no keys. Inline tables have key clash potential, as they extend the common key path name space.

TOM04 Key Clash Potential
-------

Because every item in a valid document must have a unique path, and there are multiple ways in a TOML document to specify the same key path, it doesn't take very long to find out that key path can only be used once. After which, a later attempt to use a key path in a duplicate, or an equivalent but different way, will lead to the parsing software discovering that the key/path already exists in the target internal data structure.


Technically, a TOML document looks like an old-fashioned INI file, with "sections" and key-value pairs. Such a resemblence might lead one to dismiss TOM04 because of its poor cousin relation.  In TOM04 section headers now form a namspace of "key-paths", with the dot character  '.' as seperators for key chains in multi-level key-value and table/arrays tree, no restrictions in key chain depth, or number of keys at any node. 

JSON its YAML self-declared super-set also code for array/table structured data. These are also intended to be text formats, that are human readable to a degree, in their less technical more open format formulations, but software that writes data using the full technical specifications to maximise full unambiguous data transmission, uses cryptic compressed indications of data types and and string lengths, and forgoes the helpful multi-line indentations, and significant spaces that aid human readability. 

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

But table names are fully specified. If I want a table called 'physical' inside the current 'blah' leaf, I cannot do it by specifying 'physical', because that is an entirely new unshared subroot. Here is what I think a test case should produce, from the above toml document. Its in my first comment from my fork, in the branch called 'nearyou', as the only change.  Of course its in my 'mdrynn' branch commits in both the parse-utils and toml repository forks, because I sort of went troppo on it.  My enthusiasm is going to dissipate soon, because I have other stuff to do.



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


The specification page gives an example , and also the equivalent expected JSON structure.

"You can create nested arrays of tables as well. Just use the same double bracket syntax on sub-tables. Each double-bracketed sub-table will belong to the most recently defined table element above it."

Here is their cut-and-pasted tom example. The example (not the words) also shows that single bracketed sub-table belongs to the most recently defined table element (ie the 0-indexed first table in 'fruit' AOT)

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

This behaviour can't be implemented with naive, AOT context free, table creation.

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

 AOT establish a key path, that to the human reader, and config file writer, is overtly, without the numeric indexes. A double brackets means the virtual path formed by the latest AOT member definitions. Its always going to be AOTRoot.#.NextLevel.#.  What the human writer, and meaning intention, is just the meaning of give me the latest of [[AOTRoot.NextLevel]]   I use # here in the rough Lua PL sense, of count or last numeric member of table.

and a table with [AOTRoot.{NextLevel.}SomeLeaf] fits into that scheme, and is no longer a standalone, if their is no shared AOT context. It actually becomes AOTRoot.[Latest#].[NextLevel].[Latest#].SomeLeaf

Because the example on the TOML site is very specific and clear, I now distrust all those so called compliant software packages for the various programming languages and libraries, that they may don't implement this correctly. Even though I haven't tried any of them yet, other than PHP. (Other than Yosymphony, there was another PHP candidate that I was using initially, and it was working pretty well, and it claimed compliance, until I started to notice it wasn't exactly tested with rigor, and your version appears to have been built with a consistent discipline of design and test together, and used the phpunit framework. Still I did try a few ideas using the other PHP that claimed 0.4.0 support (https://github.com/leonelquinteros/php-toml), and it is pretty good compliance.

Its possible that 0.4.0 doesn't actually do AOT contexts, and this is a new feature that will be further specified in 1.0.  However the history, intentions and slated level of the TOML site AOT context example is lacking.

So the new TOML seems to be shaping up with this sort of rule : -

A [table] or AOT that shares any part of a previously declared AOT path, and is an implicit nested child, by the main key components, is nested in the context of that shared path using the current last table members of the shared parts of the AOT path.

I've thought of using a hybrid AOT path - table naming scheme, which would be along the lines of
[[fruit].physical]  which means literally fruit.#.physical, where # means current last table in AOT, that was created by [[fruit]], or even created by [[fruit].physical] itself, if no [[fruit]] previously declared.
