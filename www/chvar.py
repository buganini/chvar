import sys
import os
import json
import re
from aiohttp import web

attrs = ("CN","JP","TW","CP950","CP936","GB2312","GBK")

class Chvar():
    def __init__(self, datadir):
        self.datadir = datadir
        self.reload()

    def reload(self):
        self.group1 = {}
        self.group2 = {}
        self.attr1 = {}
        self.attr2 = {}
        self.g1map = {}
        self.g2map = {}

        with open(os.path.join(self.datadir, "group1.txt")) as f:
            for l in f:
                l = l.strip().upper()
                if not l:
                    continue
                g1, cp = l.split("\t")
                if not cp in self.g1map:
                    self.g1map[cp] = []
                self.g1map[cp].append(g1)
                if not g1 in self.group1:
                    self.group1[g1] = []
                self.group1[g1].append(cp)

        with open(os.path.join(self.datadir, "group2.txt")) as f:
            for l in f:
                l = l.strip().upper()
                if not l:
                    continue
                g2, g1 = l.split("\t")
                if not g1 in self.g2map:
                    self.g2map[g1] = []
                self.g2map[g1].append(g2)
                if not g2 in self.group2:
                    self.group2[g2] = []
                self.group2[g2].append(g1)

        with open(os.path.join(self.datadir, "attr1.txt")) as f:
            header = next(f).strip().split("\t")
            for l in f:
                l = l.strip().upper()
                if not l:
                    continue
                l = l.split("\t")
                d = {}
                for i in range(1, len(l)):
                    d[header[i]] = l[i]
                self.attr1[l[0]] = d

        with open(os.path.join(self.datadir, "attr2.txt")) as f:
            header = next(f).strip().split("\t")
            for l in f:
                l = l.strip().upper()
                if not l:
                    continue
                l = l.split("\t")
                d = {}
                for i in range(1, len(l)):
                    d[header[i]] = l[i]
                self.attr2[l[0]] = d

    def query(self, q):
        if re.match(r"^[A-Fa-f0-9]+$", q):
            qs = [q]
        else:
            qs = ["{:X}".format(ord(x)) for x in q]
        data = {}
        tokens = []
        for cp in qs:
            token = [cp, False]
            tokens.append(token)
            if not cp in self.g1map:
                continue
            for g1 in self.g1map[cp]:
                if g1 in self.g2map:
                    for g2 in self.g2map[g1]:
                        if g2 in self.group2:
                            data[g2] =  {"virtual":False, "children": {}}
                            for g1 in self.group2[g2]:
                                data[g2]["children"][g1] = self.group1[g1]
                                for c in self.group1[g1]:
                                    if c==cp:
                                        token[1] = True
                else:
                    data["g1/{}".format(g1)] = {"virtual":True, "children":{g1:self.group1[g1]}}
                    for c in self.group1[g1]:
                        if c==cp:
                            token[1] = True
        for g2 in data:
            g2glyph = []
            for g1 in data[g2]["children"]:
                glyph = data[g2]["children"][g1]
                g2glyph.extend(glyph)
                d = [{"codepoint":x, "virtual":False} for x in glyph]
                if g1 in self.attr1:
                    for a in attrs:
                        v = self.attr1[g1].get(a)
                        if not v or v in glyph:
                            continue
                        d.append({"codepoint":v, "virtual":True})
                        glyph.append(v)
                d.sort(key=lambda x:(x["virtual"], len(x["codepoint"]), x["codepoint"]))
                for g in d:
                    g["attr"] = {}
                    for a in attrs:
                        v = self.attr1[g1].get(a)
                        g["attr"][a] = v == g["codepoint"]
                data[g2]["children"][g1] = d
            if data[g2]["virtual"]:
                continue
            g2glyph = list(set(g2glyph))
            d = [{"codepoint":x, "virtual":False} for x in g2glyph]
            if g2 in self.attr2:
                for a in attrs:
                    v = self.attr2[g2].get(a)
                    if not v or v in g2glyph:
                        continue
                    d.append({"codepoint":v, "virtual":True})
                    g2glyph.append(v)
            d.sort(key=lambda x:(x["virtual"], len(x["codepoint"]), x["codepoint"]))
            for g in d:
                g["attr"] = {}
                for a in attrs:
                    v = self.attr2[g2].get(a)
                    g["attr"][a] = v == g["codepoint"]
            data[g2]["glyph"] = d
        return {"query":tokens, "data":data}

cv = Chvar(sys.argv[1])
if 2 < len(sys.argv):
    print(json.dumps(cv.query(sys.argv[2]), indent=4))
else:
    async def handle(request):
        if 'reload' in request.GET:
            print("Reload")
            cv.reload()
        q = request.GET.get('q')
        ret = cv.query(q)
        print(json.dumps(ret, indent=4))
        return web.json_response(ret, headers={"Access-Control-Allow-Origin":"*"})

    app = web.Application()
    app.router.add_get('/', handle)

    web.run_app(app)
