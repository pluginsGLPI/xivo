![Logo GLPI](./img/glpi.png) 

![Logo XIVO](./img/xivo.png) 

![Logo WAZO](./img/wazo.png) 

Connector for [GLPI](http://glpi-project.org) with [XIVO](https://www.xivo.solutions/) or [WAZO](http://wazo.community/)

**[DOWNLOAD the plugin](https://github.com/pluginsGLPI/xivo/releases)**

## Features

Here is the list of currently working/planned features:

- [x] Phones inventory
- [x] Lines inventory
- [ ] Users presence
- [ ] Auto-open tickets or users form
- [ ] Click2Call
- [ ] Call logs
- [ ] Directory

Please contact [Teclib'](http://www.teclib-group.com) by [mail](http://www.teclib-group.com/contact/) or phone (+33 1 79 97 02 78) if you want informations for developing futures one.

## Configuration

This plugin was tested with a xivo version 16.12 and should working correctly for versions above 16.04

### Inventory

We need a webservices user with these minimal acl:

- confd.users.read
- confd.devices.#.read
- confd.lines.#.read
- confd.devices.read
- confd.lines.read

## Contributing

* Open an [issue](https://github.com/pluginsGLPI/xivo/issues/new) for each bug/feature so it can be discussed
* Follow [development guidelines](http://glpi-developer-documentation.readthedocs.io/en/latest/plugins.html)
* Refer to [GitFlow](http://git-flow.readthedocs.io/) process for branching
* Work on a new branch on your own fork
* Open a PR that will be reviewed by a developer

## Screenshots

<img src="https://github.com/pluginsGLPI/xivo/blob/master/screenshots/inventory_phones.png?raw=true" alt="inventory of phones"  style="width: 250px;"/>
<img src="https://github.com/pluginsGLPI/xivo/blob/master/screenshots/inventory_lines.png?raw=true" alt="inventory of lines"  style="width: 250px;"/>
