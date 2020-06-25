#!/bin/sh

# Get transliteration table: (CP950, CP936, GB2312, GBK)
python3 chvar . transliterate CP950 | python3 bsdconv-to.py CP950 > ../bsdconv/modules/to/CP950-TRANS.txt
python3 chvar . transliterate CP936 | python3 bsdconv-to.py CP936 > ../bsdconv/modules/to/CP936-TRANS.txt
python3 chvar . transliterate GB2312 | python3 bsdconv-to.py GB2312 > ../bsdconv/modules/to/GB2312-TRANS.txt
python3 chvar . transliterate GBK | python3 bsdconv-to.py GBK > ../bsdconv/modules/to/GBK-TRANS.txt

# Get normalization table: (TW, CN, JP, KO)
python3 chvar . normalize TW | python3 bsdconv-inter.py > ../bsdconv/modules/inter/ZHTW.txt
python3 chvar . normalize CN | python3 bsdconv-inter.py > ../bsdconv/modules/inter/ZHCN.txt
python3 chvar . normalize JP | python3 bsdconv-inter.py > ../bsdconv/modules/inter/KANJI.txt
python3 chvar . normalize KO | python3 bsdconv-inter.py > ../bsdconv/modules/inter/HANJA.txt

# Get fuzzy table: (TW, CN)
python3 chvar . fuzzy TW | python3 bsdconv-inter.py > ../bsdconv/modules/inter/ZH-FUZZY-TW.txt
python3 chvar . fuzzy CN | python3 bsdconv-inter.py > ../bsdconv/modules/inter/ZH-FUZZY-CN.txt
