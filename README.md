https://bsdconv.io/chvar

Run local server:
```
python3 www/server.py .
```

Get transliteration table: (CP950, CP936, GB2312, GBK)
```
python3 chvar . transliterate CP950 | python3 bsdconv-to.py CP950
```


Get normalization table: (TW, CN, JP)
```
python3 chvar . normalize TW | python3 bsdconv-inter.py
```


Get fuzzy table: (TW, CN)
```
python3 chvar . fuzzy TW | python3 bsdconv-inter.py
```