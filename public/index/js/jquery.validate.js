 /**
 * jQuery Validation Plugin @VERSION
 *
 * http://bassistance.de/jquery-plugins/jquery-plugin-validation/
 * http://docs.jquery.com/Plugins/Validation
 *
 * Copyright (c) 2012 J枚rn Zaefferer
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */

;
(function(c) {
    c.extend(c.fn, {validate: function(a) {
            if (this.length) {
                var b = c.data(this[0], "validator");
                if (b)
                    return b;
                //this.attr("novalidate", "novalidate");
                b = new c.validator(a, this[0]);
                c.data(this[0], "validator", b);
                b.settings.onsubmit && (this.validateDelegate(":submit", "click", function(a) {
                    b.settings.submitHandler && (b.submitButton = a.target);
                    c(a.target).hasClass("cancel") && (b.cancelSubmit = !0)
                }), this.submit(function(a) {
                    function e() {
                        var e;
                        return b.settings.submitHandler ? (b.submitButton && (e = c("<input type='hidden'/>").attr("name", b.submitButton.name).val(b.submitButton.value).appendTo(b.currentForm)), b.settings.submitHandler.call(b, b.currentForm, a), b.submitButton && e.remove(), !1) : !0
                    }
                    b.settings.debug && a.preventDefault();
                    if (b.cancelSubmit)
                        return b.cancelSubmit = !1, e();
                    if (b.form())
                        return b.pendingRequest ? (b.formSubmitted = !0, !1) : e();
                    b.focusInvalid();
                    return !1
                }));
                return b
            }
            a && (a.debug && window.console) && console.warn("nothing selected, can't validate, returning nothing")
        },valid: function() {
            if (c(this[0]).is("form"))
                return this.validate().form();
            var a = !0, b = c(this[0].form).validate();
            this.each(function() {
                a &= b.element(this)
            });
            return a
        },removeAttrs: function(a) {
            var b = {}, d = this;
            c.each(a.split(/\s/), function(a, c) {
                b[c] = d.attr(c);
                d.removeAttr(c)
            });
            return b
        },rules: function(a, b) {
            var d = this[0];
            if (a) {
                var e = c.data(d.form, "validator").settings, f = e.rules, g = c.validator.staticRules(d);
                switch (a) {
                    case "add":
                        c.extend(g, c.validator.normalizeRule(b));
                        f[d.name] = g;
                        b.messages && (e.messages[d.name] = c.extend(e.messages[d.name], b.messages));
                        break;
                    case "remove":
                        if (!b)
                            return delete f[d.name], g;
                        var h = {};
                        c.each(b.split(/\s/), function(a, b) {
                            h[b] = g[b];
                            delete g[b]
                        });
                        return h
                }
            }
            d = c.validator.normalizeRules(c.extend({}, c.validator.metadataRules(d), c.validator.classRules(d), c.validator.attributeRules(d), c.validator.staticRules(d)), d);
            d.required && (e = d.required, delete d.required, d = c.extend({required: e}, d));
            return d
        }});
    c.extend(c.expr[":"], {blank: function(a) {
            return !c.trim("" + a.value)
        },filled: function(a) {
            return !!c.trim("" + a.value)
        },unchecked: function(a) {
            return !a.checked
        }});
    c.validator = function(a, b) {
        this.settings = c.extend(!0, {}, c.validator.defaults, a);
        this.currentForm = b;
        this.init()
    };
    c.validator.format = function(a, b) {
        if (1 === arguments.length)
            return function() {
                var b = c.makeArray(arguments);
                b.unshift(a);
                return c.validator.format.apply(this, b)
            };
        2 < arguments.length && b.constructor !== Array && (b = c.makeArray(arguments).slice(1));
        b.constructor !== Array && (b = [b]);
        c.each(b, function(b, c) {
            a = a.replace(RegExp("\\{" + b + "\\}", "g"), c)
        });
        return a
    };
    c.extend(c.validator, {defaults: {messages: {},groups: {},rules: {},errorClass: "error",validClass: "valid",errorElement: "label",focusInvalid: !0,errorContainer: c([]),errorLabelContainer: c([]),onsubmit: !0,ignore: ":hidden",ignoreTitle: !1,onfocusin: function(a) {
                this.lastActive = a;
                this.settings.focusCleanup && !this.blockFocusCleanup && (this.settings.unhighlight && this.settings.unhighlight.call(this, a, this.settings.errorClass, this.settings.validClass), this.addWrapper(this.errorsFor(a)).hide())
            },onfocusout: function(a) {
                !this.checkable(a) && (a.name in this.submitted || !this.optional(a)) && this.element(a)
            },onkeyup: function(a, b) {
                9 == b.which && "" === this.elementValue(a) || (a.name in this.submitted || a === this.lastActive) && this.element(a)
            },onclick: function(a) {
                a.name in this.submitted ? this.element(a) : a.parentNode.name in this.submitted && this.element(a.parentNode)
            },highlight: function(a, b, d) {
                "radio" === a.type ? this.findByName(a.name).addClass(b).removeClass(d) : c(a).addClass(b).removeClass(d)
            },unhighlight: function(a, b, d) {
                "radio" === a.type ? this.findByName(a.name).removeClass(b).addClass(d) : c(a).removeClass(b).addClass(d)
            }},setDefaults: function(a) {
            c.extend(c.validator.defaults, a)
        },messages: {required: "This field is required.",remote: "Please fix this field.",email: "Please enter a valid email address.",url: "Please enter a valid URL.",date: "Please enter a valid date.",dateISO: "Please enter a valid date (ISO).",number: "Please enter a valid number.",digits: "Please enter only digits.",creditcard: "Please enter a valid credit card number.",equalTo: "Please enter the same value again.",maxlength: c.validator.format("Please enter no more than {0} characters."),minlength: c.validator.format("Please enter at least {0} characters."),rangelength: c.validator.format("Please enter a value between {0} and {1} characters long."),range: c.validator.format("Please enter a value between {0} and {1}."),max: c.validator.format("Please enter a value less than or equal to {0}."),min: c.validator.format("Please enter a value greater than or equal to {0}.")},autoCreateRanges: !1,prototype: {init: function() {
                function a(a) {
                    var b = c.data(this[0].form, "validator"), d = "on" + a.type.replace(/^validate/, "");
                    b.settings[d] && b.settings[d].call(b, this[0], a)
                }
                this.labelContainer = c(this.settings.errorLabelContainer);
                this.errorContext = this.labelContainer.length && this.labelContainer || c(this.currentForm);
                this.containers = c(this.settings.errorContainer).add(this.settings.errorLabelContainer);
                this.submitted = {};
                this.valueCache = {};
                this.pendingRequest = 0;
                this.pending = {};
                this.invalid = {};
                this.reset();
                var b = this.groups = {};
                c.each(this.settings.groups, function(a, d) {
                    c.each(d.split(/\s/), function(c, d) {
                        b[d] = a
                    })
                });
                var d = this.settings.rules;
                c.each(d, function(a, b) {
                    d[a] = c.validator.normalizeRule(b)
                });
                c(this.currentForm).validateDelegate(":text, [type='password'], [type='file'], select, textarea, [type='number'], [type='search'] ,[type='tel'], [type='url'], [type='email'], [type='datetime'], [type='date'], [type='month'], [type='week'], [type='time'], [type='datetime-local'], [type='range'], [type='color'] ", "focusin focusout keyup", a).validateDelegate("[type='radio'], [type='checkbox'], select, option", "click", a);
                this.settings.invalidHandler && c(this.currentForm).bind("invalid-form.validate", this.settings.invalidHandler)
            },form: function() {
                this.checkForm();
                c.extend(this.submitted, this.errorMap);
                this.invalid = c.extend({}, this.errorMap);
                this.valid() || c(this.currentForm).triggerHandler("invalid-form", [this]);
                this.showErrors();
                return this.valid()
            },checkForm: function() {
               /*this.prepareForm();
				for ( var i = 0, elements = (this.currentElements = this.elements()); elements[i]; i++ ) {
					this.check( elements[i] );
				}
				return this.valid();*/
				this.prepareForm();
				for ( var i = 0, elements = (this.currentElements = this.elements()); elements[i]; i++ ) {
					if(elements[i].name == "classprice_mark[]" || elements[i].name == "classprice_mark_info[]"|| elements[i].name == "classprice_aff[]" || elements[i].name == "classprice_adv[]"  ){ 
						if (this.findByName( elements[i].name ).length != undefined && this.findByName( elements[i].name ).length > 1) {
							for (var cnt = 0; cnt < this.findByName( elements[i].name ).length; cnt++) {
									this.check( this.findByName( elements[i].name )[cnt] );
							}
						} else {
							this.check( elements[i] );
						}
					}else{
						this.check( elements[i] );
					}
				}
				return this.valid();
            },element: function(a) {
                this.lastElement = a = this.validationTargetFor(this.clean(a));
                this.prepareElement(a);
                this.currentElements = c(a);
                var b = !1 !== this.check(a);
                b ? delete this.invalid[a.name] : this.invalid[a.name] = !0;
                this.numberOfInvalids() || (this.toHide = this.toHide.add(this.containers));
                this.showErrors();
                return b
            },showErrors: function(a) {
                if (a) {
                    c.extend(this.errorMap, a);
                    this.errorList = [];
                    for (var b in a)
                        this.errorList.push({message: a[b],element: this.findByName(b)[0]});
                    this.successList = c.grep(this.successList, function(b) {
                        return !(b.name in a)
                    })
                }
                this.settings.showErrors ? this.settings.showErrors.call(this, this.errorMap, this.errorList) : this.defaultShowErrors()
            },resetForm: function() {
                c.fn.resetForm && c(this.currentForm).resetForm();
                this.submitted = {};
                this.lastElement = null;
                this.prepareForm();
                this.hideErrors();
                this.elements().removeClass(this.settings.errorClass).removeData("previousValue")
            },numberOfInvalids: function() {
                return this.objectLength(this.invalid)
            },objectLength: function(a) {
                var b = 0, c;
                for (c in a)
                    b++;
                return b
            },hideErrors: function() {
                this.addWrapper(this.toHide).hide()
            },valid: function() {
                return 0 === this.size()
            },size: function() {
                return this.errorList.length
            },focusInvalid: function() {
                if (this.settings.focusInvalid)
                    try {
                        c(this.findLastActive() || this.errorList.length && this.errorList[0].element || []).filter(":visible").focus().trigger("focusin")
                    } catch (a) {
                    }
            },findLastActive: function() {
                var a = this.lastActive;
                return a && 1 === c.grep(this.errorList, function(b) {
                    return b.element.name === a.name
                }).length && a
            },elements: function() {
                var a = this, b = {};
                return c(this.currentForm).find("input, select, textarea").not(":submit, :reset, :image, [disabled]").not(this.settings.ignore).filter(function() {
                    !this.name && (a.settings.debug && window.console) && console.error("%o has no name assigned", this);
                    return this.name in b || !a.objectLength(c(this).rules()) ? !1 : b[this.name] = !0
                })
            },clean: function(a) {
                return c(a)[0]
            },errors: function() {
                var a = this.settings.errorClass.replace(" ", ".");
                return c(this.settings.errorElement + "." + a, this.errorContext)
            },reset: function() {
                this.successList = [];
                this.errorList = [];
                this.errorMap = {};
                this.toShow = c([]);
                this.toHide = c([]);
                this.currentElements = c([])
            },prepareForm: function() {
                this.reset();
                this.toHide = this.errors().add(this.containers)
            },prepareElement: function(a) {
                this.reset();
                this.toHide = this.errorsFor(a)
            },elementValue: function(a) {
                var b = c(a).attr("type"), d = c(a).val();
                return "radio" === b || "checkbox" === b ? c('input[name="' + c(a).attr("name") + '"]:checked').val() : "string" === typeof d ? d.replace(/\r/g, "") : d
            },check: function(a) {
                var a = this.validationTargetFor(this.clean(a)), b = c(a).rules(), d = !1, e = this.elementValue(a), f, g;
                for (g in b) {
                    var h = {method: g,parameters: b[g]};
                    try {
                        if (f = c.validator.methods[g].call(this, e, a, h.parameters), "dependency-mismatch" === f)
                            d = !0;
                        else {
                            d = !1;
                            if ("pending" === f) {
                                this.toHide = this.toHide.not(this.errorsFor(a));
                                return
                            }
                            if (!f)
                                return this.formatAndAdd(a, h), !1
                        }
                    } catch (j) {
                        throw this.settings.debug && window.console && console.log("exception occured when checking element " + a.id + ", check the '" + h.method + "' method", j), j;
                    }
                }
                if (!d)
                    return this.objectLength(b) && this.successList.push(a), !0
            },customMetaMessage: function(a, b) {
                if (c.metadata) {
                    var d = this.settings.meta ? c(a).metadata()[this.settings.meta] : c(a).metadata();
                    return d && d.messages && d.messages[b]
                }
            },customDataMessage: function(a, b) {
                return c(a).data("msg-" + b.toLowerCase()) || a.attributes && c(a).attr("data-msg-" + b.toLowerCase())
            },customMessage: function(a, b) {
                var c = this.settings.messages[a];
                return c && (c.constructor === String ? c : c[b])
            },findDefined: function() {
                for (var a = 0; a < arguments.length; a++)
                    if (void 0 !== arguments[a])
                        return arguments[a]
            },defaultMessage: function(a, b) {
                return this.findDefined(this.customMessage(a.name, b), this.customDataMessage(a, b), this.customMetaMessage(a, b), !this.settings.ignoreTitle && a.title || void 0, c.validator.messages[b], "<strong>Warning: No message defined for " + a.name + "</strong>")
            },formatAndAdd: function(a, b) {
                var d = this.defaultMessage(a, b.method), e = /\$?\{(\d+)\}/g;
                "function" === typeof d ? d = d.call(this, b.parameters, a) : e.test(d) && (d = c.validator.format(d.replace(e, "{$1}"), b.parameters));
                this.errorList.push({message: d,element: a});
                this.errorMap[a.name] = d;
                this.submitted[a.name] = d
            },addWrapper: function(a) {
                this.settings.wrapper && (a = a.add(a.parent(this.settings.wrapper)));
                return a
            },defaultShowErrors: function() {
                var a, b;
                for (a = 0; this.errorList[a]; a++)
                    b = this.errorList[a], this.settings.highlight && this.settings.highlight.call(this, b.element, this.settings.errorClass, this.settings.validClass), this.showLabel(b.element, b.message);
                this.errorList.length && (this.toShow = this.toShow.add(this.containers));
                if (this.settings.success)
                    for (a = 0; this.successList[a]; a++)
                        this.showLabel(this.successList[a]);
                if (this.settings.unhighlight) {
                    a = 0;
                    for (b = this.validElements(); b[a]; a++)
                        this.settings.unhighlight.call(this, b[a], this.settings.errorClass, this.settings.validClass)
                }
                this.toHide = this.toHide.not(this.toShow);
                this.hideErrors();
                this.addWrapper(this.toShow).show()
            },validElements: function() {
                return this.currentElements.not(this.invalidElements())
            },invalidElements: function() {
                return c(this.errorList).map(function() {
                    return this.element
                })
            },showLabel: function(a, b) {
                var d = this.errorsFor(a);
                d.length ? (d.removeClass(this.settings.validClass).addClass(this.settings.errorClass), d.attr("generated") && d.html(b)) : (d = c("<" + this.settings.errorElement + "/>").attr({"for": this.idOrName(a),generated: !0}).addClass(this.settings.errorClass).html(b || ""), this.settings.wrapper && (d = d.hide().show().wrap("<" + this.settings.wrapper + "/>").parent()), this.labelContainer.append(d).length || (this.settings.errorPlacement ? this.settings.errorPlacement(d, c(a)) : d.insertAfter(a)));
                !b && this.settings.success && (d.text(""), "string" === typeof this.settings.success ? d.addClass(this.settings.success) : this.settings.success(d, a));
                this.toShow = this.toShow.add(d)
            },errorsFor: function(a) {
                var b = this.idOrName(a);
                return this.errors().filter(function() {
                    return c(this).attr("for") === b
                })
            },idOrName: function(a) {
                return this.groups[a.name] || (this.checkable(a) ? a.name : a.id || a.name)
            },validationTargetFor: function(a) {
                this.checkable(a) && (a = this.findByName(a.name).not(this.settings.ignore)[0]);
                return a
            },checkable: function(a) {
                return /radio|checkbox/i.test(a.type)
            },findByName: function(a) {
                return c(this.currentForm).find('[name="' + a + '"]')
            },getLength: function(a, b) {
                switch (b.nodeName.toLowerCase()) {
                    case "select":
                        return c("option:selected", b).length;
                    case "input":
                        if (this.checkable(b))
                            return this.findByName(b.name).filter(":checked").length
                }
                return a.length
            },depend: function(a, b) {
                return this.dependTypes[typeof a] ? this.dependTypes[typeof a](a, b) : !0
            },dependTypes: {"boolean": function(a) {
                    return a
                },string: function(a, b) {
                    return !!c(a, b.form).length
                },"function": function(a, b) {
                    return a(b)
                }},optional: function(a) {
                var b = this.elementValue(a);
                return !c.validator.methods.required.call(this, b, a) && "dependency-mismatch"
            },startRequest: function(a) {
                this.pending[a.name] || (this.pendingRequest++, this.pending[a.name] = !0)
            },stopRequest: function(a, b) {
                this.pendingRequest--;
                0 > this.pendingRequest && (this.pendingRequest = 0);
                delete this.pending[a.name];
                b && 0 === this.pendingRequest && this.formSubmitted && this.form() ? (c(this.currentForm).submit(), this.formSubmitted = !1) : !b && (0 === this.pendingRequest && this.formSubmitted) && (c(this.currentForm).triggerHandler("invalid-form", [this]), this.formSubmitted = !1)
            },previousValue: function(a) {
                return c.data(a, "previousValue") || c.data(a, "previousValue", {old: null,valid: !0,message: this.defaultMessage(a, "remote")})
            }},classRuleSettings: {required: {required: !0},email: {email: !0},url: {url: !0},date: {date: !0},dateISO: {dateISO: !0},number: {number: !0},digits: {digits: !0},creditcard: {creditcard: !0}},addClassRules: function(a, b) {
            a.constructor === String ? this.classRuleSettings[a] = b : c.extend(this.classRuleSettings, a)
        },classRules: function(a) {
            var b = {};
            (a = c(a).attr("class")) && c.each(a.split(" "), function() {
                this in c.validator.classRuleSettings && c.extend(b, c.validator.classRuleSettings[this])
            });
            return b
        },attributeRules: function(a) {
            var b = {}, a = c(a), d;
            for (d in c.validator.methods) {
                var e;
                "required" === d ? (e = a.get(0).getAttribute(d), "" === e && (e = !0), e = !!e) : e = a.attr(d);
                e ? b[d] = e : a[0].getAttribute("type") === d && (b[d] = !0)
            }
            b.maxlength && /-1|2147483647|524288/.test(b.maxlength) && delete b.maxlength;
            return b
        },metadataRules: function(a) {
            if (!c.metadata)
                return {};
            var b = c.data(a.form, "validator").settings.meta;
            return b ? c(a).metadata()[b] : c(a).metadata()
        },staticRules: function(a) {
            var b = {}, d = c.data(a.form, "validator");
            d.settings.rules && (b = c.validator.normalizeRule(d.settings.rules[a.name]) || {});
            return b
        },normalizeRules: function(a, b) {
            c.each(a, function(d, e) {
                if (!1 === e)
                    delete a[d];
                else if (e.param || e.depends) {
                    var f = !0;
                    switch (typeof e.depends) {
                        case "string":
                            f = !!c(e.depends, b.form).length;
                            break;
                        case "function":
                            f = e.depends.call(b, b)
                    }
                    f ? a[d] = void 0 !== e.param ? e.param : !0 : delete a[d]
                }
            });
            c.each(a, function(d, e) {
                a[d] = c.isFunction(e) ? e(b) : e
            });
            c.each(["minlength", "maxlength", "min", "max"], function() {
                a[this] && (a[this] = Number(a[this]))
            });
            c.each(["rangelength", "range"], function() {
                a[this] && (a[this] = [Number(a[this][0]), Number(a[this][1])])
            });
            if (c.validator.autoCreateRanges && (a.min && a.max && (a.range = [a.min, a.max], delete a.min, delete a.max), a.minlength && a.maxlength))
                a.rangelength = [a.minlength, a.maxlength], delete a.minlength, delete a.maxlength;
            a.messages && delete a.messages;
            return a
        },normalizeRule: function(a) {
            if ("string" === typeof a) {
                var b = {};
                c.each(a.split(/\s/), function() {
                    b[this] = !0
                });
                a = b
            }
            return a
        },addMethod: function(a, b, d) {
            c.validator.methods[a] = b;
            c.validator.messages[a] = void 0 !== d ? d : c.validator.messages[a];
            3 > b.length && c.validator.addClassRules(a, c.validator.normalizeRule(a))
        },methods: {required: function(a, b, d) {
                return !this.depend(d, b) ? "dependency-mismatch" : "select" === b.nodeName.toLowerCase() ? (a = c(b).val()) && 0 < a.length : this.checkable(b) ? 0 < this.getLength(a, b) : 0 < c.trim(a).length
            },remote: function(a, b, d) {
                if (this.optional(b))
                    return "dependency-mismatch";
                var e = this.previousValue(b);
                this.settings.messages[b.name] || (this.settings.messages[b.name] = {});
                e.originalMessage = this.settings.messages[b.name].remote;
                this.settings.messages[b.name].remote = e.message;
                d = "string" === typeof d && {url: d} || d;
                if (this.pending[b.name])
                    return "pending";
                if (e.old === a)
                    return e.valid;
                e.old = a;
                var f = this;
                this.startRequest(b);
                var g = {};
                g[b.name] = a;
                c.ajax(c.extend(!0, {url: d,mode: "abort",port: "validate" + b.name,dataType: "json",data: g,success: function(d) {
                        f.settings.messages[b.name].remote = e.originalMessage;
                        var g = d === true || d === "true";
                        if (g) {
                            var i = f.formSubmitted;
                            f.prepareElement(b);
                            f.formSubmitted = i;
                            f.successList.push(b);
                            delete f.invalid[b.name];
                            f.showErrors()
                        } else {
                            i = {};
                            d = d || f.defaultMessage(b, "remote");
                            i[b.name] = e.message = c.isFunction(d) ? d(a) : d;
                            f.invalid[b.name] = true;
                            f.showErrors(i)
                        }
                        e.valid = g;
                        f.stopRequest(b, g)
                    }}, d));
                return "pending"
            },minlength: function(a, b, d) {
                a = c.isArray(a) ? a.length : this.getLength(c.trim(a), b);
                return this.optional(b) || a >= d
            },maxlength: function(a, b, d) {
                a = c.isArray(a) ? a.length : this.getLength(c.trim(a), b);
                return this.optional(b) || a <= d
            },rangelength: function(a, b, d) {
                a = c.isArray(a) ? a.length : this.getLength(c.trim(a), b);
                return this.optional(b) || a >= d[0] && a <= d[1]
            },min: function(a, b, c) {
                return this.optional(b) || a >= c
            },max: function(a, b, c) {
                return this.optional(b) || a <= c
            },range: function(a, b, c) {
                return this.optional(b) || a >= c[0] && a <= c[1]
            },email: function(a, b) {
                return this.optional(b) || /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))$/i.test(a)
            },url: function(a, b) {
				a = 'http://'+a;
                return this.optional(b) || /^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(a)
            },date: function(a, b) {
                return this.optional(b) || !/Invalid|NaN/.test(new Date(a))
            },dateISO: function(a, b) {
                return this.optional(b) || /^\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}$/.test(a)
            },number: function(a, b) {
                return this.optional(b) || /^-?(?:\d+|\d{1,3}(?:,\d{3})+)?(?:\.\d+)?$/.test(a)
            },digits: function(a, b) {
                return this.optional(b) || /^\d+$/.test(a)
            },creditcard: function(a, b) {
                if (this.optional(b))
                    return "dependency-mismatch";
                if (/[^0-9 \-]+/.test(a))
                    return !1;
                for (var c = 0, e = 0, f = !1, a = a.replace(/\D/g, ""), g = a.length - 1; 0 <= g; g--) {
                    e = a.charAt(g);
                    e = parseInt(e, 10);
                    if (f && 9 < (e *= 2))
                        e -= 9;
                    c += e;
                    f = !f
                }
                return 0 === c % 10
            },equalTo: function(a, b, d) {
                d = c(d);
                this.settings.onfocusout && d.unbind(".validate-equalTo").bind("blur.validate-equalTo", function() {
                    c(b).valid()
                });
                return a === d.val()
            }}});
    c.format = c.validator.format
})(jQuery);
(function(c) {
    var a = {};
    if (c.ajaxPrefilter)
        c.ajaxPrefilter(function(b, c, f) {
            c = b.port;
            "abort" === b.mode && (a[c] && a[c].abort(), a[c] = f)
        });
    else {
        var b = c.ajax;
        c.ajax = function(d) {
            var e = ("port" in d ? d : c.ajaxSettings).port;
            return "abort" === ("mode" in d ? d : c.ajaxSettings).mode ? (a[e] && a[e].abort(), a[e] = b.apply(this, arguments)) : b.apply(this, arguments)
        }
    }
})(jQuery);
(function(c) {
    !jQuery.event.special.focusin && (!jQuery.event.special.focusout && document.addEventListener) && c.each({focus: "focusin",blur: "focusout"}, function(a, b) {
        function d(a) {
            a = c.event.fix(a);
            a.type = b;
            return c.event.handle.call(this, a)
        }
        c.event.special[b] = {setup: function() {
                this.addEventListener(a, d, !0)
            },teardown: function() {
                this.removeEventListener(a, d, !0)
            },handler: function(a) {
                var d = arguments;
                d[0] = c.event.fix(a);
                d[0].type = b;
                return c.event.handle.apply(this, d)
            }}
    });
    c.extend(c.fn, {validateDelegate: function(a, b, d) {
            return this.bind(b, function(b) {
                var f = c(b.target);
                if (f.is(a))
                    return d.apply(f, arguments)
            })
        }})
})(jQuery);
