fix-code-style:
	@ -  ./vendor/bin/php-cs-fixer fix --config=.php_cs.dist.php --allow-risky=yes --verbose --using-cache=no

analysis-code:
	@ - ./vendor/bin/psalm --show-info=true
