<template>
    <div class="loop-page">
        <Headline class="visible-xs">
            <span slot="title">

            </span>
        </Headline>

        <div id="loop">
            <a href="https://soundcloud.com/looph0le" target="_blank" id="sc"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEEAAAAeCAYAAABzL3NnAAAABGdBTUEAALGOfPtRkwAAACBjSFJNAACHDwAAjA8AAP1SAACBQAAAfXkAAOmLAAA85QAAGcxzPIV3AAAKOWlDQ1BQaG90b3Nob3AgSUNDIHByb2ZpbGUAAEjHnZZ3VFTXFofPvXd6oc0wAlKG3rvAANJ7k15FYZgZYCgDDjM0sSGiAhFFRJoiSFDEgNFQJFZEsRAUVLAHJAgoMRhFVCxvRtaLrqy89/Ly++Osb+2z97n77L3PWhcAkqcvl5cGSwGQyhPwgzyc6RGRUXTsAIABHmCAKQBMVka6X7B7CBDJy82FniFyAl8EAfB6WLwCcNPQM4BOB/+fpFnpfIHomAARm7M5GSwRF4g4JUuQLrbPipgalyxmGCVmvihBEcuJOWGRDT77LLKjmNmpPLaIxTmns1PZYu4V8bZMIUfEiK+ICzO5nCwR3xKxRoowlSviN+LYVA4zAwAUSWwXcFiJIjYRMYkfEuQi4uUA4EgJX3HcVyzgZAvEl3JJS8/hcxMSBXQdli7d1NqaQffkZKVwBALDACYrmcln013SUtOZvBwAFu/8WTLi2tJFRbY0tba0NDQzMv2qUP91829K3NtFehn4uWcQrf+L7a/80hoAYMyJarPziy2uCoDOLQDI3fti0zgAgKSobx3Xv7oPTTwviQJBuo2xcVZWlhGXwzISF/QP/U+Hv6GvvmckPu6P8tBdOfFMYYqALq4bKy0lTcinZ6QzWRy64Z+H+B8H/nUeBkGceA6fwxNFhImmjMtLELWbx+YKuGk8Opf3n5r4D8P+pMW5FonS+BFQY4yA1HUqQH7tBygKESDR+8Vd/6NvvvgwIH554SqTi3P/7zf9Z8Gl4iWDm/A5ziUohM4S8jMX98TPEqABAUgCKpAHykAd6ABDYAasgC1wBG7AG/iDEBAJVgMWSASpgA+yQB7YBApBMdgJ9oBqUAcaQTNoBcdBJzgFzoNL4Bq4AW6D+2AUTIBnYBa8BgsQBGEhMkSB5CEVSBPSh8wgBmQPuUG+UBAUCcVCCRAPEkJ50GaoGCqDqqF6qBn6HjoJnYeuQIPQXWgMmoZ+h97BCEyCqbASrAUbwwzYCfaBQ+BVcAK8Bs6FC+AdcCXcAB+FO+Dz8DX4NjwKP4PnEIAQERqiihgiDMQF8UeikHiEj6xHipAKpAFpRbqRPuQmMorMIG9RGBQFRUcZomxRnqhQFAu1BrUeVYKqRh1GdaB6UTdRY6hZ1Ec0Ga2I1kfboL3QEegEdBa6EF2BbkK3oy+ib6Mn0K8xGAwNo42xwnhiIjFJmLWYEsw+TBvmHGYQM46Zw2Kx8lh9rB3WH8vECrCF2CrsUexZ7BB2AvsGR8Sp4Mxw7rgoHA+Xj6vAHcGdwQ3hJnELeCm8Jt4G749n43PwpfhGfDf+On4Cv0CQJmgT7AghhCTCJkIloZVwkfCA8JJIJKoRrYmBRC5xI7GSeIx4mThGfEuSIemRXEjRJCFpB+kQ6RzpLuklmUzWIjuSo8gC8g5yM/kC+RH5jQRFwkjCS4ItsUGiRqJDYkjiuSReUlPSSXK1ZK5kheQJyeuSM1J4KS0pFymm1HqpGqmTUiNSc9IUaVNpf+lU6RLpI9JXpKdksDJaMm4ybJkCmYMyF2TGKQhFneJCYVE2UxopFykTVAxVm+pFTaIWU7+jDlBnZWVkl8mGyWbL1sielh2lITQtmhcthVZKO04bpr1borTEaQlnyfYlrUuGlszLLZVzlOPIFcm1yd2WeydPl3eTT5bfJd8p/1ABpaCnEKiQpbBf4aLCzFLqUtulrKVFS48vvacIK+opBimuVTyo2K84p6Ss5KGUrlSldEFpRpmm7KicpFyufEZ5WoWiYq/CVSlXOavylC5Ld6Kn0CvpvfRZVUVVT1Whar3qgOqCmrZaqFq+WpvaQ3WCOkM9Xr1cvUd9VkNFw08jT6NF454mXpOhmai5V7NPc15LWytca6tWp9aUtpy2l3audov2Ax2yjoPOGp0GnVu6GF2GbrLuPt0berCehV6iXo3edX1Y31Kfq79Pf9AAbWBtwDNoMBgxJBk6GWYathiOGdGMfI3yjTqNnhtrGEcZ7zLuM/5oYmGSYtJoct9UxtTbNN+02/R3Mz0zllmN2S1zsrm7+QbzLvMXy/SXcZbtX3bHgmLhZ7HVosfig6WVJd+y1XLaSsMq1qrWaoRBZQQwShiXrdHWztYbrE9Zv7WxtBHYHLf5zdbQNtn2iO3Ucu3lnOWNy8ft1OyYdvV2o/Z0+1j7A/ajDqoOTIcGh8eO6o5sxybHSSddpySno07PnU2c+c7tzvMuNi7rXM65Iq4erkWuA24ybqFu1W6P3NXcE9xb3Gc9LDzWepzzRHv6eO7yHPFS8mJ5NXvNelt5r/Pu9SH5BPtU+zz21fPl+3b7wX7efrv9HqzQXMFb0ekP/L38d/s/DNAOWBPwYyAmMCCwJvBJkGlQXlBfMCU4JvhI8OsQ55DSkPuhOqHC0J4wybDosOaw+XDX8LLw0QjjiHUR1yIVIrmRXVHYqLCopqi5lW4r96yciLaILoweXqW9KnvVldUKq1NWn46RjGHGnIhFx4bHHol9z/RnNjDn4rziauNmWS6svaxnbEd2OXuaY8cp40zG28WXxU8l2CXsTphOdEisSJzhunCruS+SPJPqkuaT/ZMPJX9KCU9pS8Wlxqae5Mnwknm9acpp2WmD6frphemja2zW7Fkzy/fhN2VAGasyugRU0c9Uv1BHuEU4lmmfWZP5Jiss60S2dDYvuz9HL2d7zmSue+63a1FrWWt78lTzNuWNrXNaV78eWh+3vmeD+oaCDRMbPTYe3kTYlLzpp3yT/LL8V5vDN3cXKBVsLBjf4rGlpVCikF84stV2a9021DbutoHt5turtn8sYhddLTYprih+X8IqufqN6TeV33zaEb9joNSydP9OzE7ezuFdDrsOl0mX5ZaN7/bb3VFOLy8qf7UnZs+VimUVdXsJe4V7Ryt9K7uqNKp2Vr2vTqy+XeNc01arWLu9dn4fe9/Qfsf9rXVKdcV17w5wD9yp96jvaNBqqDiIOZh58EljWGPft4xvm5sUmoqbPhziHRo9HHS4t9mqufmI4pHSFrhF2DJ9NProje9cv+tqNWytb6O1FR8Dx4THnn4f+/3wcZ/jPScYJ1p/0Pyhtp3SXtQBdeR0zHYmdo52RXYNnvQ+2dNt293+o9GPh06pnqo5LXu69AzhTMGZT2dzz86dSz83cz7h/HhPTM/9CxEXbvUG9g5c9Ll4+ZL7pQt9Tn1nL9tdPnXF5srJq4yrndcsr3X0W/S3/2TxU/uA5UDHdavrXTesb3QPLh88M+QwdP6m681Lt7xuXbu94vbgcOjwnZHokdE77DtTd1PuvriXeW/h/sYH6AdFD6UeVjxSfNTws+7PbaOWo6fHXMf6Hwc/vj/OGn/2S8Yv7ycKnpCfVEyqTDZPmU2dmnafvvF05dOJZ+nPFmYKf5X+tfa5zvMffnP8rX82YnbiBf/Fp99LXsq/PPRq2aueuYC5R69TXy/MF72Rf3P4LeNt37vwd5MLWe+x7ys/6H7o/ujz8cGn1E+f/gUDmPP8usTo0wAAAAlwSFlzAAALEgAACxIB0t1+/AAAABh0RVh0U29mdHdhcmUAcGFpbnQubmV0IDQuMC41ZYUyZQAABZVJREFUWEfl2XWIbVUUx/GnYrfYiIWJ3QEWio3dgd0YYyt2dyAmopjY3d3dih3Y3d3+PsM5w8xxv/HemXnXP96DL+fOOjf2Wnut31r7vGFdXV0jPUXjyEbR+D8yWVgrnBnuDc+Hp8KN4eCwSBgjlD47YIrGDjNWGC+sFL4Of/fDX+GFsHkYM5S+r22Kxg5zXrgr7BZKjpcQjCfC7KH0nW1RNHYAu39GmDHcHl4Ou4eSw/0hc1YMpd9omaJxCBle/c4QOLFxuCe8GvasbO3yQ1g6lH6nJYrGQbJomDksEB6tbAeG2cJEwb1ZAwc2DQ+Et8LelW0gfBimCs21tETROABGCdJytHBR2DesEj4J7n0QiBmFp/hq2eK3DA+H94LPNJ1rh0tCaW3/SdE4AEYPX4ZJwpXhsLBGZVMSn4Ztwgnh7TBnsPDtw5NBkPavbAPl9zBfKK2vX4rGNuCg3j5uUJtThxvC8WG98F2YIHwVdgqnhveDxVr4LuGZ8HE4oLINhrNDaZ39UjS2wTLh3DBx+CXMFKj9WWGT8HMQJMEgfBapROiFRbMpjy+CUmk61S60QVaW1jpcisY2WC1cG6YMv4U5wv3h/LBVZZsuCAZxNBN8HpYMFm33dYZvwqGVbTCYH+YOpbUOl6KxDTYMdwSO/hEWDI+FS4N6V6dE8NdwRCCaSoOIWjTH3wjfBzrSdGogmDeILKG8PBwblg3jhJIPLQVh/GDnvD4oUPtZglq329qglmcXlgpq/Jqwa/gzCIxgEMXLwrdBBlnwUYFQ/hQEqenQQJB91tLbZhMIsLU0/es3CPr5FEF/rwXn6WDac8ARXaPui2Ge4Mfs8EvhlqDv14GRJaeFqwMB3SB4/8lBe7TwYyrbiMQGrBD6+NrnjwbSefmweLgqOLB8Fgw82uD6gZi9ExYOfsQJ8M1wd6ABbCsHGUFAdQ76YGZwz+hMzARJR6kXOyLhw7Shx9feTtcQFrutRjcLUtdUN2mQVtMHx9ydw3HBDFALHY2wsw+FOr3XCTKCHtwa7LoAu3dO0B4F6aTK1gn6tNLeztecErQ6Cn9I2CK8FpSHxS4UtDXpqyyImozx5d7LKfUnQGwC6Uqk7gy+wz824qU9eq1cXDuBbKBp3T43AwB1u1y4LVwQ9gkmP2cCXyC93w2CdGGQHesG92iEzHguCCabIclVK72veu07a5tu4bXZwrVT9IhkMwCwi9uFZ4OdOzFI4bWDDxt/BUV90wbKv0NwT+aYAxyNa6f2qq43hUeq197nenMwI3hNM1w7hdLt9rkZAGhxdk/Kq30KLgiE0IfVs91T31cEQdg6uGf4kWo6BNFj889V0B6sXteiyVY/TaIPrp3CtNvtczMAuC5odbLg4mDwsFBdwodXDQ487lm4Hl8PP1TfPQOTGYBto0AYDVAGK51gx+Ce3f8o0AkZx9YJ6JhxvtvnZgBAoAw/9ODw4Lj7ejAgWax2KN0dho4OzgJ1i3RyNAZzVt1z3jzB8dODAAtaPScQz3pYOrKydQLrMPR1+9wMABYLRkwBoPZapDQWOSLokZgWyMk9wivBQFUPRp796QR0gvPOEz4nM3QDGkB4LUb5eHA6VAeoVjCnOMD1+Nzb+SacMF0JSnNYUssCJP0FxAGKNswVlJEycYq0w5MH6Ucgpb/uMW+wINpj7Ha8HuzzhFawRvNNH1/7/NHAwcfzAaVhHmDzfwCCoTXShjXD9cEj8x/DNEGLNSitHuxw/VBl20BktVelZVFmCEEbzDPGVpClNtDGNf3sNwg1HpjUPdVuqiUPQ+z6/EEajxqcF7x3v+CBivtqz+dk0hKBSJocJwyPB1ri/TqJEqE9xu6hwglVKSs7J93efvVQNA4htfgIUm2rX3seWdtqjOtjDyG+r/kb/6JoHLnoGvYPWJ1z1qGgieAAAAAASUVORK5CYII=" /></a>
        </div>
    </div>
</template>

<script>
    import Headline from './Headline.vue';

    export default {
        components: {
            Headline,
        },
        mounted() {
            const width = '100%';
            const height = '100%';
            const src = "/static/2018/loopnull.swf?_=2018-ny";

            const objectParams = {
                movie: src, quality: "medium", bgcolor: "#2C333D"
            };

            const object = document.createElement("object");
            object.setAttribute("classid", "clsid:D27CDB6E-AE6D-11cf-96B8-444553540000");
            object.setAttribute("width",   width);
            object.setAttribute("height",  height);

            for (let i in objectParams) if (objectParams.hasOwnProperty(i)) {
                const param = document.createElement("param");
                param.setAttribute("name",  i);
                param.setAttribute("value", objectParams[i]);
                object.appendChild(param);
            }

            const embed = document.createElement("embed");
            embed.setAttribute("width",    	width);
            embed.setAttribute("height",   	height);
            embed.setAttribute("type",	  	"application/x-shockwave-flash");
            embed.setAttribute("name",	  	"loopnull");
            embed.setAttribute("bgcolor",  	objectParams.bgcolor);
            embed.setAttribute("quality",  	objectParams.quality);
            embed.setAttribute("salign",  	"topleft");
            embed.setAttribute("src",	   	src);
            embed.setAttribute("href", 		src);
            object.appendChild(embed);

            document.getElementById("loop").appendChild(object);
        }
    }
</script>

<style lang="scss" rel="stylesheet/scss">
    @import "~assets/styles/_vars";

    .loop-page {
        background: $color-blue-dk;
        position: absolute;
        bottom: 0;
        top: 0;
        right: 0;
        left: 0;

        #loop {
            position: absolute;
            bottom: 0;
            top: 0;
            right: 0;
            left: 0;
        }
        #sc {
            position: fixed;
            display: block;
            bottom: 5px;
            right: 5px;
        }
    }

</style>
