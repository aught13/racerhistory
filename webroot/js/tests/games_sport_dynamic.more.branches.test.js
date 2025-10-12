/* eslint-env jest */
describe('games_sport_dynamic additional branches', () => {
    beforeEach(() => {
        document.body.innerHTML =
            '<select id="team-season-select" data-sport-url="/test"></select><div id="sport-specific-section"></div><span id="current-sport"></span><span id="sport-loading"></span>';
        jest.resetModules();
    });

    test('buildFieldControl creates number with min/max and falls back to text when type unsupported', () => {
        // require after DOM ready so module initializes
        const gs = require('../../js/games_sport_dynamic');
        const buildFieldControl =
            gs.buildFieldControl || (gs.__internals && gs.__internals.buildFieldControl);
        const metaNumber = { field_name: 'testnum', field_type: 'number', min: '1', max: '10' };

        const control = buildFieldControl(metaNumber, {});
        expect(control).toBeTruthy();
        const input = control.querySelector('input') || control.querySelector('textarea');
        expect(input).toBeTruthy();
        expect(input.getAttribute('min')).toBe('1');
        expect(input.getAttribute('max')).toBe('10');

        const metaOther = { field_name: 'oth', field_type: 'unsupported' };
        const control2 = buildFieldControl(metaOther, {});
        const input2 = control2.querySelector('input');
        expect(input2).toBeTruthy();
        expect(input2.getAttribute('type')).toBe('text');
    });

    test('groupFields groups controls by group id', () => {
        const gs = require('../../js/games_sport_dynamic');
        const groupFields = gs.groupFields || (gs.__internals && gs.__internals.groupFields);
        const fields = [{ field_group: '1' }, { field_group: '1' }, { field_group: '2' }];

        const groups = groupFields(fields);
        expect(Object.keys(groups).length).toBe(2);
        expect(groups['1'].length).toBe(2);
        expect(groups['2'].length).toBe(1);
    });
});
