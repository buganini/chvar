import sys
import os
import json
import re
from aiohttp import web

attrs = ("TW","CN","JP","CP950","CP936","GB2312","GBK")

class Chvar():
    def __init__(self, datadir):
        self.datadir = datadir
        self.checkout()

    def checkout(self):
        self.group1 = {}
        self.group2 = {}
        self.attr1 = {}
        self.attr2 = {}
        self.g1map = {}
        self.g2map = {}
        self.attr1map = {}
        self.attr2map = {}

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
                for a in attrs:
                    v = d.get(a, "")
                    if not v:
                        continue
                    if not v in self.attr1map:
                        self.attr1map[v] = []
                    self.attr1map[v].append(l[0])

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
                for a in attrs:
                    v = d.get(a, "")
                    if not v:
                        continue
                    if not v in self.attr2map:
                        self.attr2map[v] = []
                    self.attr2map[v].append(l[0])

    def query(self, query):
        data = {}
        tokenmap = {}
        todo = list(query)
        done = []

        while todo:
            cp = todo.pop(0)
            done.append(cp)

            for g1 in self.attr1map.get(cp, []):
                for c in self.group1[g1]:
                    if not c in done:
                        todo.append(c)
            for g2 in self.attr2map.get(cp, []):
                for g1 in self.group2[g2]:
                    for c in self.group1[g1]:
                        if not c in done:
                            todo.append(c)

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
                                        tokenmap[cp] = True
                                if g1 in self.attr1:
                                    for t in attrs:
                                        v = self.attr1[g1].get(t)
                                        if v and not v in done:
                                            todo.append(v)
                            if g2 in self.attr2:
                                for t in attrs:
                                    v = self.attr2[g2].get(t)
                                    if v and not v in done:
                                        todo.append(v)

                else:
                    data["g1/{}".format(g1)] = {"virtual":True, "children":{g1:self.group1[g1]}}
                    for c in self.group1[g1]:
                        if c==cp:
                            tokenmap[cp] = True
                    if g1 in self.attr1:
                        for t in attrs:
                            v = self.attr1[g1].get(t)
                            if v and not v in done:
                                todo.append(v)
        for g2 in data:
            g2glyph = []
            for g1 in data[g2]["children"]:
                glyph = list(data[g2]["children"][g1])
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
                        v = self.attr1.get(g1, {}).get(a)
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
                    v = self.attr2.get(g2, {}).get(a)
                    g["attr"][a] = v == g["codepoint"]
            data[g2]["glyph"] = d
        return {"query":[(cp, tokenmap.get(cp, False)) for cp in query], "data":data}

    def commit(self):
        with open(os.path.join(self.datadir, "group1.txt"), "w") as f:
            groups = list(self.group1.keys())
            groups.sort(key=lambda x: int(x))
            for g in groups:
                vs = self.group1[g]
                vs.sort(key=lambda x: int(x, 16))
                for v in vs:
                    f.write("{}\t{}\n".format(g, v))
        with open(os.path.join(self.datadir, "group2.txt"), "w") as f:
            groups = list(self.group2.keys())
            groups.sort(key=lambda x: int(x))
            for g in groups:
                vs = self.group2[g]
                vs.sort(key=lambda x: int(x))
                for v in vs:
                    f.write("{}\t{}\n".format(g, v))
        with open(os.path.join(self.datadir, "attr1.txt"), "w") as f:
            f.write("ID")
            for attr in attrs:
                f.write("\t{}".format(attr))
            f.write("\n")
            groups = list(self.attr1.keys())
            groups.sort(key=lambda x: int(x))
            for g in groups:
                attr = self.attr1[g]
                f.write("\t".join([g]+[attr.get(a, "") for a in attrs]).strip())
                f.write("\n")
        with open(os.path.join(self.datadir, "attr2.txt"), "w") as f:
            f.write("ID")
            for attr in attrs:
                f.write("\t{}".format(attr))
            f.write("\n")
            groups = list(self.attr2.keys())
            groups.sort(key=lambda x: int(x))
            for g in groups:
                attr = self.attr2[g]
                f.write("\t".join([g]+[attr.get(a, "") for a in attrs]).strip())
                f.write("\n")

def tokenize(query):
    tks = re.findall(r"([A-Fa-f0-9]+|\w)", query)
    qs = []
    for q in tks:
        if re.match(r"^[A-Fa-f0-9]+$", q):
            qs.append(q)
        else:
            qs.append("{:X}".format(ord(q)))
    return qs

dev_mode = True

cv = Chvar(sys.argv[1])
if 2 < len(sys.argv):
    print(json.dumps(cv.query(tokenize(sys.argv[2])), indent=4))
else:
    async def handle(request):
        if dev_mode:
            cv.checkout()
        q = request.GET.get('q')
        ret = cv.query(tokenize(q))
        #print(json.dumps(ret, indent=4))
        return web.json_response(ret, headers={"Access-Control-Allow-Origin":"*"})

    async def attr(request):
        data = await request.post()
        query = data["query"]
        level = data["level"]
        group = data["group"]
        attr = data["attr"]
        codepoint = data["codepoint"]
        ds = {"1":cv.attr1, "2":cv.attr2}[level]
        if not group in ds:
            ds[group] = {}
        if ds[group].get(attr) == codepoint:
            ds[group][attr] = ""
        else:
            ds[group][attr] = codepoint
        cv.commit()
        ret = cv.query(tokenize(query))
        #print(json.dumps(ret, indent=4))
        return web.json_response(ret, headers={"Access-Control-Allow-Origin":"*"})

    app = web.Application()
    app.router.add_get('/', handle)
    if dev_mode:
        app.router.add_post('/attr', attr)

    web.run_app(app)
