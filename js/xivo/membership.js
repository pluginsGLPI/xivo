/**
 * -------------------------------------------------------------------------
 * xivo plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of xivo.
 *
 * xivo is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * xivo is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with xivo. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2017-2022 by xivo plugin team.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/pluginsGLPI/xivo
 * -------------------------------------------------------------------------
 */

var Membership = {


    sendCallback : function() {
        console.error("Cti Not initialized: User not logged on");
    },
    init: function(cti) {
        this.MessageFactory.init(cti.WebsocketMessageFactory);
        this.sendCallback = cti.sendCallback;
    },
    getUserDefaultMembership: function(userId) {
        var message = this.MessageFactory.createGetUserDefaultMembership(userId);
        this.sendCallback(message);
    },
    setUserDefaultMembership: function(userId, membership) {
        var message = this.MessageFactory.createSetUserDefaultMembership(userId, membership);
        this.sendCallback(message);
    },
    setUsersDefaultMembership: function(userIds, membership) {
        var message = this.MessageFactory.createSetUsersDefaultMembership(userIds, membership);
        this.sendCallback(message);
    },
    applyUsersDefaultMembership: function(userIds) {
        var message = this.MessageFactory.createApplyUsersDefaultMembership(userIds);
        this.sendCallback(message);
    },

    MessageType: {
        USERQUEUEDEFAULTMEMBERSHIP: 'UserQueueDefaultMembership',
        USERSQUEUEDEFAULTMEMBERSHIP: 'UsersQueueDefaultMembership'
    },

    MessageFactory: {
        ctiMessageFactory: {
            createMessage: function () {
                console.error("Cti Not initialzed: User not logged on");
            }
        },
        init: function (ctiMessageFactory) {
            this.ctiMessageFactory = ctiMessageFactory;
        },
        createMessage: function (command) {
            return this.ctiMessageFactory.createMessage(command);
        },
        createGetUserDefaultMembership: function (userId) {
            var message = this.createMessage("getUserDefaultMembership");
            message.userId = userId;
            return message;
        },
        createSetUserDefaultMembership: function (userId, membership) {
            var message = this.createMessage("setUserDefaultMembership");
            message.userId = userId;
            message.membership = membership;
            return message;
        },
        createSetUsersDefaultMembership: function (userIds, membership) {
            var message = this.createMessage("setUsersDefaultMembership");
            message.userIds = userIds;
            message.membership = membership;
            return message;
        },
        createApplyUsersDefaultMembership: function(userIds) {
            var message = this.createMessage("applyUsersDefaultMembership");
            message.userIds = userIds;
            return message;
        }
    }
};
