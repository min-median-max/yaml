# Fork of PurrPHP/yaml

This repository is a fork of [PurrPHP/yaml](https://github.com/PurrPHP/yaml). It is the base
that the polyspec packages depend on: they require `purrphp/yaml` from this repository, at the
tags `v<upstream version>-p<n>`, and do not assume that upstream accepts the fixes below.

## What it fixes

- `CoreSchema` resolves plain scalars by the tag resolution table of YAML 1.2.2, section 10.3.2
  (<https://yaml.org/spec/1.2.2/#1032-tag-resolution>), exactly:
  - only the unsigned forms `0o[0-7]+` and `0x[0-9a-fA-F]+` are octal and hexadecimal
    integers; signed forms such as `+0x1` and `-0o7` are strings;
  - `[-+]?[0-9]+` is an integer, leading zeros included (`0777` is 777, not 777.0);
  - only `null|Null|NULL|~` and the empty scalar are null, only `true|True|TRUE` and
    `false|False|FALSE` are booleans, and only the listed cases of `.inf` and `.nan` are
    floats; other letter cases such as `nULL` and `tRUE` are strings.

Released as `v1.0.0-p1`, on top of upstream 1.0.0.
