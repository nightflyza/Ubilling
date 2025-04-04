[sectionname]
LABEL="log recorder debug log"
OPTION=RECORDER_DEBUG
TYPE=TRIGGER
VALUES=""
PATTERN=""
VALIDATOR=""

[somepass]
LABEL="User password or API key"
OPTION=PASSW
TYPE=PASSWORD
DEFAULT="123"

[sectionname2]
LABEL="log rotator debug log"
OPTION=ROTATOR_DEBUG
TYPE=CHECKBOX
PATTERN=""
VALIDATOR=""

[sectionname3]
LABEL="MySQL dumps max age in days before rotation"
OPTION=BACKUPS_MAX_AGE
TYPE=SELECT
VALUES="1,2,3,4,5,6,7"
PATTERN=""
VALIDATOR=""
DEFAULT="7"

[sectionname4]
LABEL="some custom name"
OPTION=CUSTNAME
TYPE=TEXT
VALUES=""
PATTERN=""
VALIDATOR=""

[sectionname5]
LABEL="Email"
OPTION=EMAIL
TYPE=TEXT
VALUES=""
PATTERN="email"
VALIDATOR=""
DEFAULT="test@somedomain.com"

[sectionname6]
LABEL="Some MAC address"
OPTION=SOME_MAC
TYPE=TEXT
VALUES=""
PATTERN="mac"
VALIDATOR="IsMacValid"
DEFAULT="14:88:92:94:94:66"

[woof]
LABEL="some non existing checkbox"
OPTION=WOOF
TYPE=CHECKBOX

[sli]
LABEL="just testing slider"
OPTION=RANGE
TYPE=SLIDER
VALUES="40..50"