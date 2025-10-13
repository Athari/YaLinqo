# Bugs / support

You should always provide the code; otherwise, it may be hard to guess what exactly you want. A sensible title is a nice bonus.

Please, no duplicates. Search existing issues before posting a support question.

# Pull requests

## Branches

The currently developed version is always in `master`, so you should always target `master` with your PRs. If it’s version-specific, target the branch of the highest relevant version.

## Commits

Please write meaningful commit messages, not just “Updated file.php,” which GitHub generates by default. Include information about what has been added, improved, or fixed. Writing a bit more is better than writing too little.

There’s no need to create separate PRs for every commit. If you push a new commit to the branch of your PR, it’ll be automatically added. This is very useful when the changes are closely related.

## Tests and documentation

All `Enumerable` methods must have 100% code coverage and full documentation, just like the rest of the methods. If you can’t write the documentation yourself, just ask an AI to do it.

## AI bots

Pull requests may be swarmed by AI bots. They often produce a lot of noise, so feel free to ignore them. If you do want to read their comments, focus on one bot (CodeRabbit is currently the most reliable).

## Code style

The standard PHP coding style should be compatible with the library’s code, except for mandatory `{}` — these are optional for single-line statements here.