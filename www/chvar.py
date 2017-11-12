import sys
import os
import json
import re
from aiohttp import web

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
        attr1 = {}
        attr2 = {}
        for cp in qs:
            if not cp in self.g1map:
                continue
            for g1 in self.g1map[cp]:
                if g1 in self.g2map:
                    for g2 in self.g2map[g1]:
                        if g2 in self.attr2:
                            attr2[g2] = self.attr2[g2]
                        if g2 in self.group2:
                            data[g2] = {}
                            for g1 in self.group2[g2]:
                                data[g2][g1] = self.group1[g1]
                                if g1 in self.attr1:
                                    attr1[g1] = self.attr1[g1]
                else:
                    data["g1/{}".format(g1)] = {g1:self.group1[g1]}
                    if g1 in self.attr1:
                        attr1[g1] = self.attr1[g1]
        return {"query":qs, "data":data, "attr1":attr1, "attr2":attr2}

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
