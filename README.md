# BCURE & PRIORITY

## Installing

### 1. Fill .env values.
```dotenv
APP_URL=http://example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=database
DB_USERNAME=username
DB_PASSWORD=password

# you can get zoho credentials on https://api-console.zoho.com/
ZOHO_EMAIL=***
ZOHO_CLIENT_ID=***
ZOHO_SECRET=***

SFTP_HOST=***
SFTP_PORT=***
SFTP_USERNAME=***
SFTP_PASSWORD=***
SFTP_ROOT=***
```

### 2. Install vendors
```bash
composer install
```

### 3. Roll out migration
```
php artisan migrate
```

### 4. Set permissions
```bash
chmod -R 777 /storage
chmod -R 777 /bootstrap
```

### 4. Run scheduler

Run system CRON. 
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Mock Documents

- **/so_in/so_4624378000002564330.txt**
```csv
"so_number"	"so_priority"	"status"	"remark"
"4624378000000419198"	"qweqwe"	"my status 2"	"some comments"
```

- **/so_docs/so_4624378000002569002.txt**
- **/documents/invoice_4624378000002569002.txt**
```csv

# so_4624378000000807962.txt
"sale order number"	"document type"	"file name"
"4624378000002569002"	"invoice"	"invoice_4624378000002569002.txt"

# invoice_4624378000000419198.txt
fsadflsakdjfl asjdfljaslkdfj lsajkd
fsadflsakdjfl asjdfljaslkdfj lsajkd
fsadflsakdjfl asjdfljaslkdfj lsajkd
fsadflsakdjfl asjdfljaslkdfj lsajkd
fsadflsakdjfl asjdfljaslkdfj lsajkd
fsadflsakdjfl asjdfljaslkdfj lsajkd
fsadflsakdjfl asjdfljaslkdfj lsajkd
fsadflsakdjfl asjdfljaslkdfj lsajkd
fsadflsakdjfl asjdfljaslkdfj lsajkd
```

- **/cases_in/c_4624378000000419223.txt**
```csv
Zoho case id	Priority task id 	Status	Note
4624378000000419223	asda	test-status-1	test-note-1
```

## Notes

1. Add validators to controller
2. Add archive collector. A command which removes files from archive dirs.
3. Implement shouldbeuniq for jobs
4. Setup supervisor