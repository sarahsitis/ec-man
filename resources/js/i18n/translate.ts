import english from '../../../lang/en.json';
import indonesian from '../../../lang/id.json';

export type Locale = 'id' | 'en';
export type Parameters = Record<string, string | number>;

export function translate(message: string | number, locale: Locale, parameters: Parameters = {}): string {
    if (typeof message === 'number') return String(message);
    const key = message.trim().replace(/\s+/g, ' ');
    const dictionary: Record<string, string> = locale === 'en' ? english : indonesian;
    let result = dictionary[key] ?? key;
    result = message.slice(0, message.length - message.trimStart().length) + result + message.slice(message.trimEnd().length);
    return result.replace(/:([a-zA-Z][a-zA-Z0-9_]*)/g, (placeholder, name: string) => Object.hasOwn(parameters, name) ? String(parameters[name]) : placeholder);
}
