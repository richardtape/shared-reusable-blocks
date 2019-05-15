./vendor/bin/codecept generate:cept functional "Test"
./vendor/bin/codecept run functional

./vendor/bin/codecept generate:feature acceptance "Test"
./vendor/bin/codecept gherkin:snippets acceptance
./vendor/bin/codecept run acceptance