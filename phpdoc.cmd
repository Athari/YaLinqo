set PATH=%PATH%;C:\Program Files (x86)\Graphviz\bin
rmdir /s /q phpdocs
php vendor/phpdocumentor/phpdocumentor/bin/phpdoc.php
echo .collapse { height: auto !important; } >> "phpdocs\docs\css\template.css"