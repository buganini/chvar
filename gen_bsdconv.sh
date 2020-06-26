#!/bin/sh

# ./gen_bsdconv.sh /path/of/bsdconv

BSDCONV=$1

# Get transliteration table: (CP950, CP936, GB2312, GBK)
python3 chvar . transliterate CP950 | python3 bsdconv-to.py CP950 > ${BSDCONV}/modules/to/CP950-TRANS.txt
python3 chvar . transliterate CP936 | python3 bsdconv-to.py CP936 > ${BSDCONV}/modules/to/CP936-TRANS.txt
python3 chvar . transliterate GB2312 | python3 bsdconv-to.py GB2312 > ${BSDCONV}/modules/to/GB2312-TRANS.txt
python3 chvar . transliterate GBK | python3 bsdconv-to.py GBK > ${BSDCONV}/modules/to/GBK-TRANS.txt

# Get BMP-transliteration table: (TW, CN)
python3 chvar . bmp-transliterate TW | python3 bsdconv-inter.py > ${BSDCONV}/modules/inter/BMP-TRANS-TW.txt
python3 chvar . bmp-transliterate CN | python3 bsdconv-inter.py > ${BSDCONV}/modules/inter/BMP-TRANS-CN.txt

# Get normalization table: (TW, CN, JP, KO)
python3 chvar . normalize TW | python3 bsdconv-inter.py > ${BSDCONV}/modules/inter/ZHTW.txt
python3 chvar . normalize CN | python3 bsdconv-inter.py > ${BSDCONV}/modules/inter/ZHCN.txt
python3 chvar . normalize JP | python3 bsdconv-inter.py > ${BSDCONV}/modules/inter/KANJI.txt
python3 chvar . normalize KO | python3 bsdconv-inter.py > ${BSDCONV}/modules/inter/HANJA.txt

# Get fuzzy table: (TW, CN)
python3 chvar . fuzzy TW | python3 bsdconv-inter.py > ${BSDCONV}/modules/inter/ZH-FUZZY-TW.txt
python3 chvar . fuzzy CN | python3 bsdconv-inter.py > ${BSDCONV}/modules/inter/ZH-FUZZY-CN.txt
