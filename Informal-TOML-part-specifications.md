
What is so obvious?
====

TOML may be Tom's obvious markup language, but there are some features of implementing readers and writers for TOML configuration files, that are less obvious, at least in the 0.4 Toml Specification, here at https://github.com/toml-lang/toml/blob/master/README.md.  From an implementer perspective, which I've now began to experience, there are some subtle details. 

Quite a few implementations already exist in a number of programming languages, so its possible for at least to easily construct a make-do simple implementation that caters for your particular text-configurations. 

Technically, a TOML document looks like an old-fashioned INI file, with "sections" and key-value pairs. Only the section headers now form a namspace of "key-paths", with the dot character  '.' as seperators for key chains in multi-level key-value and table/arrays tree, no restrictions in key chain depth, or number of keys at any node.
