# SPARQL support

**Source**: Based on the following ARC2 wiki page: https://github.com/semsol/arc2/wiki/SPARQL-

## Introduction

ARC supports all [SPARQL Query Language](http://www.w3.org/TR/rdf-sparql-query/) features ([to a certain extent](http://www.w3.org/2001/sw/DataAccess/tests/implementations)) and also a number of pragmatic extensions such as aggregates (AVG / COUNT / MAX / MIN / SUM) and write mechanisms. I tried to keep the changes to the SPARQL specification at a minimum, so that the existing grammar parser and store functionality can be re-used, and also to stick to ARC's flat learning curve.

This page documents the core differences between SPARQL and what is called "SPARQL+" in ARC2.

## SELECT

### Aggregates
```sql
SELECT COUNT(?contact) AS ?contacts WHERE {
  <#me> foaf:knows ?contact .
}
ORDER BY DESC(?contacts)
```
Note that the alias (... AS ...) has to be specified.


If you have more than a single result variable, you also have to provide GROUP BY information:
```sql
SELECT ?who COUNT(?contact) AS ?contacts WHERE {
  ?who foaf:knows ?contact .
}
GROUP BY ?who
```

ARC2 currently has a bug in the `SUM` ([100](https://github.com/semsol/arc2/issues/100)) and `AVG` ([99](https://github.com/semsol/arc2/issues/99)) function.

#### Supported aggregate functions

|         |                            AVG                             | COUNT | MIN | MAX | SUM |
|:--------|:----------------------------------------------------------:|:-----:|:---:|:---:|:---:|
| Support | x (but [bugged](https://github.com/semsol/arc2/issues/99)) |   x   |  x  |  x  |  x  |


### Supported relational terms

|         | = | != | < | > |
|:--------|:-:|:--:|:-:|:-:|
| Support | x | x  | x | x |

### Supported FILTER functions

|         | bound | datatype | isBlank | isIri | isLiteral | isUri | lang | langMatches | regex | str |
|:--------|:-----:|:--------:|:-------:|:-----:|:---------:|:-----:|:----:|:-----------:|:-----:|:---:|
| Support |   x   |    x     |    x    |   x   |     x     |   x   |  x   |      x      |   x   |  x  |

## LOAD
```sql
LOAD <http://example.com/>
```
ARC can extract triples from a variety of formats such as RDF/XML, Turtle, and HTML (eRDF, RDFa, microformats, basic Dublin Core data, OpenID Hooks, Feed links).


It is possible to add data to an existing graph:
```sql
LOAD <http://example.com/> INTO <http://example.com/archive>
```
(LOAD in SPARQL+ is syntactically compatible with SPARUL.)


## INSERT INTO
```sql
INSERT INTO <http://example.com/> {
 <#foo> <bar> "baz" .
}
```
In this INSERT form the triples have to be fully specified, variables are not allowed.


It is possible to dynamically generate the triples that should be inserted:
```sql
INSERT INTO <http://example.com/inferred> CONSTRUCT {
  ?s foaf:knows ?o .
}
WHERE {
  ?s xfn:contact ?o .
}
```
This is a simple extension to SPARQL's existing CONSTRUCT query type. It adds the triples generated in the construction step to the specified graph. **Note**: The CONSTRUCT keyword was made optional with the Jan 7th, 2008 revision, to increase the compatibility with SPARUL.


## DELETE

```sql
DELETE {
 <#foo> <bar> "baz" .
 <#foo2> <bar2> ?any .
}
```
Each specified triple will be deleted from the RDF store. It is possible to specify variables as wildcards, but they can't be used to build connected patterns. Each triple is handled as a stand-alone pattern.


FROM can be used to restrict the delete operations to selected graphs. It's also possible to not specify any triples. The whole graph will then be deleted.
```sql
DELETE FROM <http://example.com/archive>
```

DELETE can (like INSERT) be combined with a CONSTRUCT query (the CONSTRUCT keyword was made optional with the Jan 7th, 2008 revision):

```sql
DELETE FROM <http://example.com/inferred> {
  ?s rel:wouldLikeToKnow ?o .
}
WHERE {
  ?s kiss:kissed ?o .
}
```

Instead of deleting triples only in one graph, you can in all graphs by using:

```sql
DELETE {
  ?s rel:wouldLikeToKnow ?o .
}
WHERE {
  ?s kiss:kissed ?o .
}
```

## SPARQL Grammar Changes and Additions
```sql
Query ::= Prologue ( SelectQuery | ConstructQuery | DescribeQuery | AskQuery | LoadQuery | InsertQuery | DeleteQuery )

SelectQuery ::= 'SELECT' ( 'DISTINCT' | 'REDUCED' )? ( Aggregate+ | Var+ | '*' ) DatasetClause* WhereClause SolutionModifier

Aggregate ::= ( 'AVG' | 'COUNT' | 'MAX' | 'MIN' | 'SUM' ) '(' Var | '*' ')' 'AS' Var

LoadQuery ::= 'LOAD' IRIref ( 'INTO' IRIref )?

InsertQuery ::= 'INSERT' 'INTO' IRIref 'CONSTRUCT'? ConstructTemplate DatasetClause* WhereClause? SolutionModifier

DeleteQuery ::= 'DELETE' ( 'FROM' IRIref )* 'CONSTRUCT'? ConstructTemplate? DatasetClause* WhereClause? SolutionModifier

SolutionModifier ::= GroupClause? OrderClause? LimitOffsetClauses?

GroupClause ::= 'GROUP' 'BY' Var ( ',' Var )*
```
