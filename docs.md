# Documention for fly50w

Welcome to the document of fly50w.

## 1. Basic Syntax

### 1.1 Comment

In fly50w, comments starts with a `#`.

Example:

```fly50w
# As you see, writing as much as comments
# is a good way to achieve 50w lines of code.
```

### 1.2 Statements & Expressions

In fly50w, statements must end with `;`.

Unless specified, everything in fly50w (from lambdas to try...except blocks) are expressions.

Note this may be very different from other languages, as you can periodically see:

```fly50w
let f = fn () {
    # Some code...
};

for {
    break;
};

try {
    1 / 0;
} except @divisionByZeroError {
    println("Error caught!");
};
```

Note the presence of ';' symbols.

Sometimes you may not receive an error or a warning when you forget to add ';'. But it's an undefined behaviour. It may change and it will not be considered a bug. So please check carefully!

This is a feature, not a bug, as fly50w is error-oriented, so let's have some errors that mess up your coding experience!

### 1.3 Symbols and Operators
